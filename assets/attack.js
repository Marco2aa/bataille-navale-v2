document.addEventListener('DOMContentLoaded', () => {
    // Variable pour gérer le tour ("player" ou "opponent")
    let currentTurn = "player";

    // Met à jour l'affichage des plateaux d'attaque en fonction du tour actif.
    function updateAttackBoards() {
        const opponentAttackBoard = document.getElementById('opponent-attack-board');
        const playerAttackBoard = document.getElementById('player-attack-board');
        if (currentTurn === "player") {
            // Le joueur attaque : le plateau adverse est actif et le plateau du joueur est grisé.
            opponentAttackBoard.classList.remove("board-disabled");
            playerAttackBoard.classList.add("board-disabled");
        } else {
            // C'est le tour de l'adversaire (simulation) : le plateau du joueur devient actif.
            playerAttackBoard.classList.remove("board-disabled");
            opponentAttackBoard.classList.add("board-disabled");
        }
    }

    updateAttackBoards();

    // Fonction pour créer une grille (identique à celle utilisée précédemment)
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

    // Callback pour le plateau d'attaque adverse (le joueur attaque)
    function opponentAttackBoardClick(x, y, cell) {
        if (currentTurn !== "player") {
            console.log("Ce n'est pas votre tour d'attaquer.");
            return;
        }
        console.log(`Vous attaquez la case (${x}, ${y}) sur le plateau adverse.`);
        const gameId = document.getElementById('player-ship-board').dataset.gameId;
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
                if (data.message.includes("navire coulé")) {
                    cell.style.backgroundColor = "#FF0000"; // rouge pour navire coulé
                } else if (data.message.includes("Touché")) {
                    cell.style.backgroundColor = "#FFA500"; // orange pour touché
                } else {
                    cell.style.backgroundColor = "#808080"; // gris pour manqué
                }
                // Après l'attaque, passez le tour à l'adversaire (simulation)
                currentTurn = "opponent";
                updateAttackBoards();

                // Simuler l'attaque adverse après un délai (exemple 2 secondes)
                setTimeout(() => {
                    simulateOpponentAttack();
                }, 2000);
            })
            .catch(err => {
                console.error("Erreur d'attaque:", err);
                alert("Erreur lors de l'attaque.");
            });
    }

    // Fonction pour simuler une attaque adverse sur le plateau d'attaque du joueur
    function simulateOpponentAttack() {
        console.log("L'adversaire attaque...");
        // Pour la simulation, nous sélectionnons aléatoirement une case du plateau d'attaque du joueur
        const playerAttackBoard = document.getElementById('player-attack-board');
        const cells = playerAttackBoard.querySelectorAll('.board-cell');
        // Filtrer les cases qui n'ont pas déjà été attaquées (sans couleur de tir)
        const availableCells = Array.from(cells).filter(cell => !cell.style.backgroundColor);
        if (availableCells.length === 0) {
            alert("Toutes les cases ont déjà été attaquées.");
            return;
        }
        const randomIndex = Math.floor(Math.random() * availableCells.length);
        const targetCell = availableCells[randomIndex];
        const x = parseInt(targetCell.dataset.x);
        const y = parseInt(targetCell.dataset.y);
        console.log(`L'adversaire attaque la case (${x}, ${y}).`);
        // Pour cette simulation, nous marquons simplement la case comme manquée (gris)
        targetCell.style.backgroundColor = "#808080";
        alert("L'adversaire a attaqué. À vous de jouer !");
        // Le tour repasse au joueur
        currentTurn = "player";
        updateAttackBoards();
    }

    // Créer les grilles d'attaque
    createBoard('player-attack-board', (x, y, cell) => {
        // Généralement, le joueur n'attaque pas sur son propre plateau d'attaque
        console.log(`Clic sur votre plateau d'attaque, ignoré: (${x}, ${y}).`);
    });
    createBoard('opponent-attack-board', opponentAttackBoardClick);
});
