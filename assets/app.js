import './bootstrap.js';

import './styles/app.css';


document.addEventListener('DOMContentLoaded', () => {
    let selectedShip = null;             // Objet { type, size, orientation }
    let selectedFleetElement = null;     // Élément DOM de la flotte sélectionné
    const placedPlayerShips = [];        // Tableau d'objets pour les navires placés

    // Fonction renvoyant la couleur en fonction du type de navire
    function getColorForShipType(type) {
        switch (type) {
            case "Porte-avions": return "#00008B"; // Bleu foncé
            case "Cuirassé": return "#808080";     // Gris
            case "Croiseur": return "#008000";      // Vert
            case "Sous-marin": return "#800080";    // Violet
            case "Torpilleur": return "#FF0000";    // Rouge
            default: return "blue";
        }
    }

    // Gestion de la sélection dans la flotte du joueur
    const playerFleetItems = document.querySelectorAll('#player-fleet li');
    playerFleetItems.forEach(item => {
        item.addEventListener('click', () => {
            // Si on clique sur le même élément déjà sélectionné, on bascule l'orientation
            if (selectedFleetElement === item) {
                // Basculer l'orientation
                selectedShip.orientation = (selectedShip.orientation === "horizontal") ? "vertical" : "horizontal";
                item.textContent = `${item.dataset.shipType} (${item.dataset.size} cases) - ${selectedShip.orientation}`;
                console.log("Orientation modifiée:", selectedShip.orientation);
                return;
            }
            // Sinon, sélectionner cet élément et initialiser l'objet navire
            if (selectedFleetElement) {
                selectedFleetElement.classList.remove('selected');
            }
            selectedFleetElement = item;
            item.classList.add('selected');
            selectedShip = {
                type: item.dataset.shipType,
                size: parseInt(item.dataset.size),
                orientation: "horizontal" // Orientation par défaut
            };
            // Mettre à jour le texte pour afficher l'orientation
            item.textContent = `${item.dataset.shipType} (${item.dataset.size} cases) - horizontal`;
            console.log("Navire sélectionné:", selectedShip);
        });
    });

    /**
     * Crée une grille dans l'élément dont l'ID est fourni.
     * @param {string} boardId - L'ID de l'élément conteneur.
     * @param {function} cellClickCallback - Fonction appelée lors du clic sur chaque case.
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

    // Callback pour le plateau de navires du joueur (placement interactif)
    function playerShipBoardClick(x, y, cell) {
        if (!selectedShip) {
            console.log("Aucun navire sélectionné dans votre flotte.");
            return;
        }
        const boardEl = document.getElementById('player-ship-board');
        const largeur = parseInt(boardEl.dataset.largeur) || 10;
        const hauteur = parseInt(boardEl.dataset.hauteur) || 10;

        // Selon l'orientation sélectionnée, vérifier que le navire tient
        let cellsToFill = [];
        if (selectedShip.orientation === "horizontal") {
            if (x + selectedShip.size > largeur) {
                alert("Le navire ne rentre pas horizontalement à partir de cette case.");
                return;
            }
            for (let i = 0; i < selectedShip.size; i++) {
                const selector = `.board-cell[data-x="${x + i}"][data-y="${y}"]`;
                const targetCell = boardEl.querySelector(selector);
                if (!targetCell || targetCell.classList.contains('ship')) {
                    alert("Placement invalide : une ou plusieurs cases sont déjà occupées.");
                    return;
                }
                cellsToFill.push(targetCell);
            }
        } else { // vertical
            if (y + selectedShip.size > hauteur) {
                alert("Le navire ne rentre pas verticalement à partir de cette case.");
                return;
            }
            for (let i = 0; i < selectedShip.size; i++) {
                const selector = `.board-cell[data-x="${x}"][data-y="${y + i}"]`;
                const targetCell = boardEl.querySelector(selector);
                if (!targetCell || targetCell.classList.contains('ship')) {
                    alert("Placement invalide : une ou plusieurs cases sont déjà occupées.");
                    return;
                }
                cellsToFill.push(targetCell);
            }
        }
        // Si placement valide, marquer les cases et appliquer la couleur du navire
        const shipColor = getColorForShipType(selectedShip.type);
        cellsToFill.forEach(c => {
            c.classList.add('ship');
            c.style.backgroundColor = shipColor;
        });
        console.log(`Navire ${selectedShip.type} (${selectedShip.orientation}) placé à partir de (${x}, ${y}).`);

        // Enregistrer le placement sous forme d'objet
        const shipPlacement = {
            type: selectedShip.type,
            orientation: selectedShip.orientation,
            coordinates: Array.from(cellsToFill).map(cell => ({
                x: parseInt(cell.dataset.x),
                y: parseInt(cell.dataset.y)
            }))
        };
        placedPlayerShips.push(shipPlacement);
        console.log("Placements actuels du joueur:", placedPlayerShips);
        // Retirer le navire de la flotte (déselection)
        selectedFleetElement.remove();
        selectedShip = null;
        selectedFleetElement = null;
    }

    // Pour les plateaux d'attaque, on ne gère pas encore le placement
    function attackBoardClick(x, y, cell) {
        console.log(`Plateau d'attaque cliqué: (${x}, ${y}).`);
    }

    // Créer les grilles pour le joueur
    createBoard('player-ship-board', playerShipBoardClick);
    createBoard('player-attack-board', attackBoardClick);

    // Pour l'adversaire, le plateau de navires peut être non interactif (ou vous pouvez le simuler)
    createBoard('opponent-ship-board', () => {
        alert("Le placement des navires adverses se fait automatiquement.");
    });
    createBoard('opponent-attack-board', attackBoardClick);

    // Bouton de validation des placements
    const validateBtn = document.getElementById('validate-ships-btn');
    if (validateBtn) {
        validateBtn.addEventListener('click', () => {
            // Préparer le payload
            const playerBoard = document.getElementById('player-ship-board');
            const payload = {
                gameId: playerBoard.dataset.gameId,
                playerPlateauId: playerBoard.dataset.plateauId,
                // Le payload inclut le tableau des navires placés (chaque objet contient type, orientation et coordonnées)
                playerShips: placedPlayerShips,
                // Pour l'adversaire, on envoie un tableau vide pour cet exemple
                opponentPlateauId: document.getElementById('opponent-ship-board').dataset.plateauId,
                opponentShips: []
            };

            console.log("Payload à envoyer:", payload);

            fetch('/api/place-ships', {
                method: 'POST',
                headers: { 'Content-Type': 'application/json' },
                body: JSON.stringify(payload)
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error("Erreur lors de la validation des placements");
                    }
                    return response.json();
                })
                .then(data => {
                    console.log("Réponse API:", data);
                    alert("Placements validés avec succès !");
                })
                .catch(err => {
                    console.error("Erreur:", err);
                    alert("Erreur lors de la validation des placements.");
                });
        });
    }
});






console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');
