/**
 * TaskFlow — menu ⋯ des cartes + glisser-déposer entre les colonnes.
 *
 * 1. On attrape une carte (dragstart).
 * 2. Pendant qu'on la survole au-dessus d'une colonne (dragover), on la place
 *    visuellement au bon endroit.
 * 3. Au lâcher (drop), on envoie la nouvelle colonne + la position au serveur
 *    avec fetch(). En cas d'erreur, on recharge la page.
 */
(function () {
    const kanban = document.querySelector('.kanban');
    if (!kanban) return; // on n'est pas sur la page d'un tableau

    const moveUrl = kanban.dataset.moveUrl;
    const csrf = document.querySelector('meta[name="csrf-token"]').content;
    let dragged = null;

    // ---------- Menu ⋯ des cartes ----------
    // Le menu est un <details> : il s'ouvre et se ferme tout seul, même sans JavaScript.
    // Ici on ajoute juste du confort : un seul menu ouvert à la fois, fermeture au clic
    // ailleurs ou avec Échap, et pas de glisser-déposer pendant qu'un menu est ouvert.
    const menus = document.querySelectorAll('.card-menu');

    menus.forEach((menu) => {
        menu.addEventListener('toggle', () => {
            const card = menu.closest('.card');
            card.draggable = !menu.open;
            if (menu.open) {
                menus.forEach((other) => { if (other !== menu) other.open = false; });
                placePanel(menu);
            }
        });
    });

    /**
     * Place le menu en position "fixed" (par rapport à la fenêtre, pas à la page),
     * en dessous du bouton ⋯ s'il y a la place, sinon au-dessus.
     * Sa hauteur max = la place restante : s'il est trop long, c'est LUI qui défile.
     */
    function placePanel(menu) {
        const panel = menu.querySelector('.menu-panel');
        const button = menu.querySelector('summary').getBoundingClientRect();
        const margin = 12;
        const gap = 6;

        panel.style.position = 'fixed';
        panel.style.right = 'auto';
        panel.style.maxHeight = 'none';

        // Horizontal : aligné à droite du bouton, sans sortir de l'écran
        const width = panel.offsetWidth;
        const left = Math.min(Math.max(margin, button.right + 8 - width), window.innerWidth - width - margin);
        panel.style.left = left + 'px';

        // Vertical : en dessous si possible, sinon du côté où il y a le plus de place
        const spaceBelow = window.innerHeight - button.bottom - gap - margin;
        const spaceAbove = button.top - gap - margin;
        const needed = Math.min(panel.scrollHeight, 420);

        if (spaceBelow >= needed || spaceBelow >= spaceAbove) {
            panel.style.top = (button.bottom + gap) + 'px';
            panel.style.bottom = 'auto';
            panel.style.maxHeight = Math.min(420, spaceBelow) + 'px';
        } else {
            panel.style.top = 'auto';
            panel.style.bottom = (window.innerHeight - button.top + gap) + 'px';
            panel.style.maxHeight = Math.min(420, spaceAbove) + 'px';
        }
        panel.scrollTop = 0;
    }

    // Si la page défile ou que la fenêtre change de taille, on referme le menu
    // (sinon il resterait "collé" à l'écran pendant que la carte s'en va).
    const closeAll = () => menus.forEach((menu) => { menu.open = false; });
    window.addEventListener('scroll', closeAll, { passive: true });
    window.addEventListener('resize', closeAll);

    document.addEventListener('click', (event) => {
        menus.forEach((menu) => { if (menu.open && !menu.contains(event.target)) menu.open = false; });
    });

    document.addEventListener('keydown', (event) => {
        if (event.key !== 'Escape') return;
        menus.forEach((menu) => {
            if (menu.open) {
                menu.open = false;
                menu.querySelector('summary').focus();
            }
        });
    });

    // ---------- Glisser-déposer ----------
    kanban.addEventListener('dragstart', (event) => {
        const card = event.target.closest('.card');
        if (!card || !card.draggable) return;
        dragged = card;
        card.classList.add('dragging');
        event.dataTransfer.effectAllowed = 'move';
        event.dataTransfer.setData('text/plain', card.dataset.taskId);
    });

    kanban.addEventListener('dragend', () => {
        if (dragged) dragged.classList.remove('dragging');
        dragged = null;
        document.querySelectorAll('.drag-over').forEach((el) => el.classList.remove('drag-over'));
    });

    document.querySelectorAll('.card-list').forEach((list) => {
        list.addEventListener('dragover', (event) => {
            if (!dragged) return;
            event.preventDefault(); // obligatoire pour autoriser le "drop"
            list.classList.add('drag-over');

            const after = cardAfter(list, event.clientY);
            if (after) list.insertBefore(dragged, after);
            else list.appendChild(dragged);
        });

        list.addEventListener('dragleave', (event) => {
            if (!list.contains(event.relatedTarget)) list.classList.remove('drag-over');
        });

        list.addEventListener('drop', async (event) => {
            event.preventDefault();
            list.classList.remove('drag-over');
            if (!dragged) return;

            const card = dragged;
            const index = [...list.querySelectorAll('.card')].indexOf(card);
            updateCounts();
            updateDoneStyle(card, list);

            const body = new FormData();
            body.append('csrf', csrf);
            body.append('task_id', card.dataset.taskId);
            body.append('column_id', list.dataset.columnId);
            body.append('index', index);

            try {
                const response = await fetch(moveUrl, { method: 'POST', body });
                const data = await response.json();
                if (!data.ok) throw new Error(data.error);
            } catch (error) {
                alert("Le déplacement n'a pas pu être enregistré. La page va se recharger.");
                location.reload();
            }
        });
    });

    /** Trouve la carte située juste sous la souris (pour insérer avant elle). */
    function cardAfter(list, mouseY) {
        const cards = [...list.querySelectorAll('.card:not(.dragging)')];
        return cards.find((card) => {
            const box = card.getBoundingClientRect();
            return mouseY < box.top + box.height / 2;
        });
    }

    /** Met à jour les petits compteurs en haut de chaque colonne. */
    function updateCounts() {
        document.querySelectorAll('.column').forEach((column) => {
            column.querySelector('[data-count]').textContent = column.querySelectorAll('.card').length;
        });
    }

    /** Une carte posée dans la colonne "Terminé" prend le style "terminé". */
    function updateDoneStyle(card, list) {
        card.classList.toggle('card-done', list.dataset.done === '1');
    }
})();
