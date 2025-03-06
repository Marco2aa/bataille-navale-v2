import './bootstrap.js';

import './styles/app.css';




document.addEventListener('DOMContentLoaded', () => {
    // ----- Phase 1 : Placement des navires -----
    let selectedPlayerShip = null;   // { type, size, orientation }
    let selectedPlayerFleetElement = null;
    const placedPlayerShips = [];    // Navires placés du joueur

    let selectedOpponentShip = null;
    let selectedOpponentFleetElement = null;
    const placedOpponentShips = [];  // Navires placés de l'adversaire

    // Fonction pour retourner une couleur en fonction du type de navire
    function getColorForShipType(type) {
        switch (type) {
            case "Porte-avions": return "#00008B"; // bleu foncé
            case "Cuirassé": return "#808080";     // gris
            case "Croiseur": return "#008000";      // vert
            case "Sous-marin": return "#800080";    // violet
            case "Torpilleur": return "#FF0000";    // rouge
            default: return "blue";
        }
    }

    // Sélection dans la flotte du joueur
    const playerFleetItems = document.querySelectorAll('#player-fleet li');
    playerFleetItems.forEach(item => {
        item.addEventListener('click', () => {
            if (selectedPlayerFleetElement) {
                selectedPlayerFleetElement.classList.remove('selected');
            }
            selectedPlayerFleetElement = item;
            item.classList.add('selected');
            selectedPlayerShip = {
                type: item.dataset.shipType,
                size: parseInt(item.dataset.size),
                orientation: "horizontal"
            };
            item.textContent = `${item.dataset.shipType} (${item.dataset.size} cases) - horizontal`;
            console.log("Navire joueur sélectionné:", selectedPlayerShip);
        });
    });

    // Sélection dans la flotte adverse (si placement manuel)
    const opponentFleetItems = document.querySelectorAll('#opponent-fleet li');
    opponentFleetItems.forEach(item => {
        item.addEventListener('click', () => {
            if (selectedOpponentFleetElement) {
                selectedOpponentFleetElement.classList.remove('selected');
            }
            selectedOpponentFleetElement = item;
            item.classList.add('selected');
            selectedOpponentShip = {
                type: item.dataset.shipType,
                size: parseInt(item.dataset.size),
                orientation: "horizontal"
            };
            item.textContent = `${item.dataset.shipType} (${item.dataset.size} cases) - horizontal`;
            console.log("Navire adverse sélectionné:", selectedOpponentShip);
        });
    });

    /**
     * Crée une grille dans l'élément dont l'ID est fourni.
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

    // Callback pour le plateau de navires du joueur (placement)
    function playerShipBoardClick(x, y, cell) {
        if (!selectedPlayerShip) {
            console.log("Aucun navire sélectionné dans votre flotte.");
            return;
        }
        const boardEl = document.getElementById('player-ship-board');
        const largeur = parseInt(boardEl.dataset.largeur) || 10;
        const hauteur = parseInt(boardEl.dataset.hauteur) || 10;
        let cellsToFill = [];
        if (selectedPlayerShip.orientation === "horizontal") {
            if (x + selectedPlayerShip.size > largeur) {
                alert("Votre navire ne rentre pas horizontalement à partir de cette case.");
                return;
            }
            for (let i = 0; i < selectedPlayerShip.size; i++) {
                const selector = `.board-cell[data-x="${x + i}"][data-y="${y}"]`;
                const targetCell = boardEl.querySelector(selector);
                if (!targetCell || targetCell.classList.contains('ship')) {
                    alert("Placement invalide : une ou plusieurs cases sont déjà occupées.");
                    return;
                }
                cellsToFill.push(targetCell);
            }
        } else { // verticale
            if (y + selectedPlayerShip.size > hauteur) {
                alert("Votre navire ne rentre pas verticalement à partir de cette case.");
                return;
            }
            for (let i = 0; i < selectedPlayerShip.size; i++) {
                const selector = `.board-cell[data-x="${x}"][data-y="${y + i}"]`;
                const targetCell = boardEl.querySelector(selector);
                if (!targetCell || targetCell.classList.contains('ship')) {
                    alert("Placement invalide : une ou plusieurs cases sont déjà occupées.");
                    return;
                }
                cellsToFill.push(targetCell);
            }
        }
        const shipColor = getColorForShipType(selectedPlayerShip.type);
        cellsToFill.forEach(c => {
            c.classList.add('ship');
            c.style.backgroundColor = shipColor;
        });
        console.log(`Navire ${selectedPlayerShip.type} (${selectedPlayerShip.orientation}) placé à partir de (${x}, ${y}).`);
        const shipPlacement = {
            type: selectedPlayerShip.type,
            orientation: selectedPlayerShip.orientation,
            coordinates: cellsToFill.map(cell => ({
                x: parseInt(cell.dataset.x),
                y: parseInt(cell.dataset.y)
            }))
        };
        placedPlayerShips.push(shipPlacement);
        console.log("Placements actuels du joueur:", placedPlayerShips);
        selectedPlayerFleetElement.remove();
        selectedPlayerShip = null;
        selectedPlayerFleetElement = null;
    }

    // Callback pour le plateau de navires adverse (optionnel si vous placez manuellement)
    function opponentShipBoardClick(x, y, cell) {
        if (!selectedOpponentShip) {
            console.log("Aucun navire sélectionné dans la flotte adverse.");
            return;
        }
        const boardEl = document.getElementById('opponent-ship-board');
        const largeur = parseInt(boardEl.dataset.largeur) || 10;
        const hauteur = parseInt(boardEl.dataset.hauteur) || 10;
        let cellsToFill = [];
        if (selectedOpponentShip.orientation === "horizontal") {
            if (x + selectedOpponentShip.size > largeur) {
                alert("Le navire adverse ne rentre pas horizontalement à partir de cette case.");
                return;
            }
            for (let i = 0; i < selectedOpponentShip.size; i++) {
                const selector = `.board-cell[data-x="${x + i}"][data-y="${y}"]`;
                const targetCell = boardEl.querySelector(selector);
                if (!targetCell || targetCell.classList.contains('ship')) {
                    alert("Placement invalide pour l'adversaire : une ou plusieurs cases sont déjà occupées.");
                    return;
                }
                cellsToFill.push(targetCell);
            }
        } else { // verticale
            if (y + selectedOpponentShip.size > hauteur) {
                alert("Le navire adverse ne rentre pas verticalement à partir de cette case.");
                return;
            }
            for (let i = 0; i < selectedOpponentShip.size; i++) {
                const selector = `.board-cell[data-x="${x}"][data-y="${y + i}"]`;
                const targetCell = boardEl.querySelector(selector);
                if (!targetCell || targetCell.classList.contains('ship')) {
                    alert("Placement invalide pour l'adversaire : une ou plusieurs cases sont déjà occupées.");
                    return;
                }
                cellsToFill.push(targetCell);
            }
        }
        const shipColor = getColorForShipType(selectedOpponentShip.type);
        cellsToFill.forEach(c => {
            c.classList.add('ship');
            c.style.backgroundColor = shipColor;
        });
        console.log(`Navire adverse ${selectedOpponentShip.type} (${selectedOpponentShip.orientation}) placé à partir de (${x}, ${y}).`);
        const shipPlacement = {
            type: selectedOpponentShip.type,
            orientation: selectedOpponentShip.orientation,
            coordinates: cellsToFill.map(cell => ({
                x: parseInt(cell.dataset.x),
                y: parseInt(cell.dataset.y)
            }))
        };
        placedOpponentShips.push(shipPlacement);
        console.log("Placements actuels de l'adversaire:", placedOpponentShips);
        selectedOpponentFleetElement.remove();
        selectedOpponentShip = null;
        selectedOpponentFleetElement = null;
    }

    // Callback pour le plateau d'attaque du joueur
    function playerAttackBoardClick(x, y, cell) {
        console.log(`Votre tir sur la case (${x}, ${y}).`);
        // Ici, vous pouvez implémenter la logique d'attaque (par exemple, marquer la case, envoyer la requête, etc.)
    }

    // Callback pour le plateau d'attaque de l'adversaire
    function opponentAttackBoardClick(x, y, cell) {
        console.log(`Tir sur le plateau adverse : case (${x}, ${y}).`);
        // Ici, vous pouvez implémenter la logique d'attaque contre l'adversaire
    }

    // Créer les grilles
    createBoard('player-ship-board', playerShipBoardClick);
    createBoard('player-attack-board', playerAttackBoardClick);
    createBoard('opponent-ship-board', opponentShipBoardClick);
    createBoard('opponent-attack-board', opponentAttackBoardClick);

    // Boutons de validation pour chaque phase
    const validatePlayerBtn = document.getElementById('validate-player-btn');
    const validateOpponentBtn = document.getElementById('validate-opponent-btn');

    // Phase 1 : Validation du placement des navires du joueur
    if (validatePlayerBtn) {
        validatePlayerBtn.addEventListener('click', () => {
            if (placedPlayerShips.length === 0) {
                alert("Vous devez placer au moins un navire.");
                return;
            }
            document.getElementById('player-ship-board').classList.add('board-disabled');
            document.getElementById('opponent-ship-board').classList.remove('board-disabled');
            validateOpponentBtn.disabled = false;
            alert("Vos navires ont été validés. Placez maintenant les navires de l'adversaire.");
        });
    }

    // Phase 2 : Validation du placement des navires adverses et envoi final
    if (validateOpponentBtn) {
        validateOpponentBtn.addEventListener('click', () => {
            const opponentBoard = document.getElementById('opponent-ship-board');
            const payload = {
                gameId: document.getElementById('player-ship-board').dataset.gameId,
                playerPlateauId: document.getElementById('player-ship-board').dataset.plateauId,
                opponentPlateauId: opponentBoard.dataset.plateauId,
                playerShips: placedPlayerShips,
                opponentShips: placedOpponentShips
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

    function attackBoardClick(x, y, cell) {
        console.log(`Vous attaquez la case (${x}, ${y}).`);
        const playerShipBoard = document.getElementById('player-ship-board');
        const gameId = playerShipBoard.dataset.gameId;
        const opponentAttackBoard = document.getElementById('opponent-attack-board');
        const opponentPlateauId = opponentAttackBoard.dataset.plateauId;
        const payload = {
            gameId: gameId,
            opponentPlateauId: opponentPlateauId,
            coordinate: { x: x, y: y }
        };
        fetch('/api/attack', {
            method: 'POST',
            headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify(payload)
        })
            .then(response => {
                if (!response.ok) {
                    throw new Error("Erreur lors de l'attaque");
                }
                return response.json();
            })
            .then(data => {
                console.log("Réponse d'attaque:", data);
                alert(data.message);
                if (data.message.includes("Touché")) {
                    cell.style.backgroundColor = "#FF0000";
                } else {
                    cell.style.backgroundColor = "#808080";
                }
            })
            .catch(err => {
                console.error("Erreur d'attaque:", err);
                alert("Erreur lors de l'attaque.");
            });
    }

    // Création du plateau d'attaque adverse (si non créé)
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

    createBoard('opponent-attack-board', attackBoardClick);
});

console.log('This log comes from assets/app.js - welcome to AssetMapper! 🎉');



