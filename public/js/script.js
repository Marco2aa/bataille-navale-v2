document.addEventListener('DOMContentLoaded', () => {
    // Récupération des informations du plateau depuis les data attributes
    const boardEl = document.getElementById('game-board');
    const largeur = parseInt(boardEl.dataset.largeur) || 10;
    const hauteur = parseInt(boardEl.dataset.hauteur) || 10;
    const plateauId = boardEl.dataset.plateauId;

    // Nettoyer le contenu du plateau
    boardEl.innerHTML = '';

    // Génération de la grille du plateau
    for (let y = 0; y < hauteur; y++) {
        const row = document.createElement('div');
        row.classList.add('board-row');

        for (let x = 0; x < largeur; x++) {
            const cell = document.createElement('div');
            cell.classList.add('board-cell');
            cell.dataset.x = x;
            cell.dataset.y = y;

            // Ajout d'un écouteur pour traiter le clic sur la case
            cell.addEventListener('click', () => {
                // Si la case a déjà été touchée, on ne fait rien
                if (cell.classList.contains('hit')) {
                    alert("Cette case a déjà été touchée.");
                    return;
                }
                // Envoi d'une requête AJAX vers l'API pour traiter le coup
                fetch('/api/hit', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json'
                    },
                    body: JSON.stringify({
                        x: x,
                        y: y,
                        plateauId: plateauId
                    })
                })
                    .then(response => response.json())
                    .then(data => {
                        console.log(data);
                        if (data.error) {
                            alert(data.error);
                        } else {
                            cell.classList.add('hit');
                            alert(data.message);
                        }
                    })
                    .catch(error => console.error('Error:', error));
            });

            row.appendChild(cell);
        }
        boardEl.appendChild(row);
    }
});
