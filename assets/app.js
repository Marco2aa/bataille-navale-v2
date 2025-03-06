import './bootstrap.js';

import './styles/app.css';


document.addEventListener('DOMContentLoaded', () => {
    /**
     * Crée une grille de plateau dans l'élément dont l'ID est fourni.
     * @param {string} boardId - L'ID de l'élément conteneur.
     * @param {function} cellClickCallback - Fonction appelée au clic sur chaque case.
     */
    function createBoard(boardId, cellClickCallback) {
        const boardEl = document.getElementById(boardId);
        if (!boardEl) {
            console.error(`Element with id "${boardId}" not found.`);
            return;
        }
        const largeur = parseInt(boardEl.dataset.largeur) || 10;
        const hauteur = parseInt(boardEl.dataset.hauteur) || 10;
        boardEl.innerHTML = '';

        for (let y = 0; y < hauteur; y++) {
            const row = document.createElement('div');
            row.classList.add('board-row');
            for (let x = 0; x < largeur; x++) {
                const cell = document.createElement('div');
                cell.classList.add('board-cell');
                cell.dataset.x = x;
                cell.dataset.y = y;
                cell.addEventListener('click', () => {
                    cellClickCallback(x, y, cell);
                });
                row.appendChild(cell);
            }
            boardEl.appendChild(row);
        }
    }

    // Callback pour le plateau de navires du joueur : placement interactif
    function playerShipClick(x, y, cell) {
        cell.classList.toggle('ship');
        console.log(`Votre plateau de navires : case (${x}, ${y}) cliquée.`);
    }

    // Callback pour le plateau de navires de l'adversaire : placement interactif
    function opponentShipClick(x, y, cell) {
        cell.classList.toggle('ship');
        console.log(`Plateau adversaire : case (${x}, ${y}) cliquée.`);
    }

    // Création des grilles pour le placement des bateaux
    createBoard('player-ship-board', playerShipClick);
    createBoard('opponent-ship-board', opponentShipClick);

    // Bouton de validation des placements
    const validateBtn = document.getElementById('validate-ships-btn');
    if (validateBtn) {
        validateBtn.addEventListener('click', () => {
            // Récupération des positions sur le plateau du joueur
            const playerBoard = document.getElementById('player-ship-board');
            const playerCells = playerBoard.querySelectorAll('.board-cell.ship');
            const playerShips = Array.from(playerCells).map(cell => ({
                x: parseInt(cell.dataset.x),
                y: parseInt(cell.dataset.y)
            }));

            // Récupération des positions sur le plateau de l'adversaire
            const opponentBoard = document.getElementById('opponent-ship-board');
            const opponentCells = opponentBoard.querySelectorAll('.board-cell.ship');
            const opponentShips = Array.from(opponentCells).map(cell => ({
                x: parseInt(cell.dataset.x),
                y: parseInt(cell.dataset.y)
            }));

            const payload = {
                playerPlateauId: playerBoard.dataset.plateauId,
                opponentPlateauId: opponentBoard.dataset.plateauId,
                playerShips: playerShips,
                opponentShips: opponentShips
            };

            console.log('Payload à envoyer:', payload);

            // Envoi des placements via une requête AJAX en utilisant la méthode POST
            fetch('/api/place-ships', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(payload)
            })
                .then(response => {
                    // Vérifier que la réponse est OK (statut 200-299)
                    if (!response.ok) {
                        console.error('Réponse non OK, méthode utilisée:', response.type);
                        throw new Error('Erreur lors de la validation des placements');
                    }
                    return response.json();
                })
                .then(data => {
                    console.log('Réponse API:', data);
                    alert('Placements validés avec succès !');
                    // Vous pouvez rediriger ou mettre à jour l'interface ici
                })
                .catch(error => {
                    console.error('Erreur lors de l\'envoi:', error);
                    alert('Erreur lors de la validation des placements.');
                });
        });
    }
});





console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
