<?php
session_start();
require_once __DIR__ . '/includes/Auth.php';

$auth = new Auth();

if (!isset($_SESSION['api_key'])) {
    header('Location: index.php');
    exit;
}

$api_key = $_SESSION['api_key'];
$user = $auth->getCurrentUser();

if (!$user) {
    header('Location: index.php');
    exit;
}

$paper_id = $_GET['id'] ?? null;
if (!$paper_id) {
    header('Location: dashboard.php');
    exit;
}

$user_name = $user['username'];
$user_avatar = "https://via.placeholder.com/40";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Paper Details | Book Archive</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
    <div class="dashboard-layout">
        <!-- Sidebar -->
        <aside class="sidebar">
            <div class="sidebar__header">
                <i class="ri-book-3-line sidebar__logo-icon"></i>
                <h1 class="sidebar__logo-text">Book Archive</h1>
            </div>
            <nav class="sidebar__nav">
                <ul>
                    <li><a href="dashboard.php" class="nav-item"><i class="ri-book-2-line"></i><span>All Books</span></a></li>
                    <li><a href="#" class="nav-item active"><i class="ri-article-line"></i><span>All Journals</span></a></li>
                    <li><a href="notes.php" class="nav-item"><i class="ri-sticky-note-line"></i><span>Notes</span></a></li>
                    <li><a href="#" class="nav-item"><i class="ri-share-line"></i><span>Shared</span></a></li>
                </ul>

                <div class="sidebar__section">
                    <h2 class="sidebar__section-title">Collections</h2>
                    <ul>
                        <li><a href="#" class="nav-item"><i class="ri-folder-line"></i><span>Fiction</span></a></li>
                        <li><a href="#" class="nav-item"><i class="ri-folder-line"></i><span>Non-Fiction</span></a></li>
                        <li><a href="#" class="nav-item"><i class="ri-folder-line"></i><span>Science</span></a></li>
                        <li><a href="#" class="nav-item"><i class="ri-folder-add-line"></i><span>New Collection</span></a></li>
                    </ul>
                </div>
            </nav>
            <div class="sidebar__footer">
                <a href="logout.php" class="nav-item nav-item--logout"><i class="ri-logout-box-r-line"></i><span>Logout</span></a>
            </div>
        </aside>

        <!-- Main content -->
        <main class="main-content">
            <header class="header">
                <div class="header-actions">
                    <a href="dashboard.php" class="button button--secondary">
                        <i class="ri-arrow-left-line"></i> Back to Dashboard
                    </a>
                    <div class="user-menu">
                        <button class="user-menu__toggle" aria-label="User Menu">
                            <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="User Avatar" class="user-avatar">
                            <i class="ri-arrow-down-s-line"></i>
                        </button>
                        <div class="user-menu__dropdown">
                            <div class="user-menu__info">
                                <span class="user-menu__name"><?php echo htmlspecialchars($user_name); ?></span>
                            </div>
                            <a href="#" class="user-menu__item"><i class="ri-user-line"></i> Profile</a>
                            <a href="#" class="user-menu__item"><i class="ri-settings-3-line"></i> Settings</a>
                            <hr class="user-menu__divider">
                            <a href="logout.php" class="user-menu__item user-menu__item--logout"><i class="ri-logout-box-r-line"></i> Logout</a>
                        </div>
                    </div>
                </div>
            </header>

            <div class="content-area">
                <div class="paper-details-container" id="paper-details">
                    <!-- Loading state -->
                    <div class="loading-placeholder">
                        <p>Loading paper details...</p>
                    </div>
                </div>

                <!-- Citation Section -->
                <div class="citation-section" id="citation-section" style="display: none;">
                    <div class="citation-section__header">
                        <h3>Citation</h3>
                        <div class="citation-format-selector">
                            <select id="citation-format" class="form-control">
                                <option value="apa">APA</option>
                                <option value="mla">MLA</option>
                                <option value="chicago">Chicago</option>
                                <option value="ieee">IEEE</option>
                            </select>
                        </div>
                    </div>
                    <div class="citation-section__content">
                        <div class="citation-text" id="citation-text"></div>
                        <div class="citation-actions">
                            <button class="button button--secondary" onclick="copyCitation()">
                                <i class="ri-file-copy-line"></i> Copy Citation
                            </button>
                            <button class="button" onclick="saveToNotes()">
                                <i class="ri-sticky-note-line"></i> Save to Notes
                            </button>
                        </div>
                    </div>
                </div>
            </div>
        </main>
    </div>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <style>
    .citation-section {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        margin-top: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .citation-section__header {
        display: flex;
        justify-content: space-between;
        align-items: center;
        margin-bottom: 1rem;
    }

    .citation-format-selector {
        width: 200px;
    }

    .citation-text {
        background: #f8fafc;
        padding: 1rem;
        border-radius: 4px;
        margin-bottom: 1rem;
        font-family: monospace;
    }

    .citation-actions {
        display: flex;
        gap: 1rem;
    }
    </style>

    <script>
        const apiKey = '<?php echo $api_key; ?>';
        const paperId = '<?php echo htmlspecialchars($paper_id); ?>';

        // Toast notification function
        function showToast(message, type = 'info', duration = 3000) {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `toast toast--${type}`;

            let iconClass = 'ri-information-line'; // Default icon
            if (type === 'success') iconClass = 'ri-check-line';
            if (type === 'error') iconClass = 'ri-error-warning-line';
            if (type === 'warning') iconClass = 'ri-alert-line';

            toast.innerHTML = `
                <i class="${iconClass} toast__icon"></i>
                <p class="toast__message">${message}</p>
                <button class="toast__close" onclick="this.parentElement.remove()"><i class="ri-close-line"></i></button>
            `;

            container.appendChild(toast);

            // Auto dismiss
            setTimeout(() => {
                toast.classList.add('toast--fade-out');
                toast.addEventListener('animationend', () => {
                    toast.remove();
                });
            }, duration);
        }

        // Fetch paper details
        async function loadPaperDetails() {
            try {
                // Remove version suffix if present (e.g., 1709.06308v1 -> 1709.06308)
                const basePaperId = paperId.replace(/v\d+$/, '');
                
                const response = await fetch(`/book-archive/api/journals.php?search=${encodeURIComponent(basePaperId)}&max_results=1`, {
                    headers: { 
                        'X-API-Key': apiKey,
                        'Content-Type': 'application/json'
                    }
                });
                
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                
                const data = await response.json();
                if (data.error) {
                    throw new Error(data.error);
                }
                if (!data.papers || data.papers.length === 0) {
                    throw new Error('Paper not found');
                }

                window.currentPaper = data.papers[0];
                
                // Show citation section
                document.getElementById('citation-section').style.display = 'block';
                updateCitation();

                const paper = data.papers[0];
                const detailsHtml = `
                    <div class="paper-details">
                        <div class="paper-details__content">
                            <div class="paper-details__info">
                                <h1 class="paper-details__title">${paper.title}</h1>
                                <p class="paper-details__author">${paper.authors.join(', ')}</p>
                                <p class="paper-details__meta">Published: ${new Date(paper.published).toLocaleDateString()}</p>
                                <p class="paper-details__meta">Last Updated: ${new Date(paper.updated).toLocaleDateString()}</p>
                                <div class="paper-details__description">
                                    ${paper.summary}
                                </div>
                                <div class="paper-details__actions">
                                    <a href="${paper.pdfLink}" class="button" target="_blank">
                                        <i class="ri-file-pdf-line"></i> View PDF
                                    </a>
                                    <button class="button button--secondary" onclick="showAddToCollectionModal()">
                                        <i class="ri-folder-add-line"></i> Add to Collection
                                    </button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                document.getElementById('paper-details').innerHTML = detailsHtml;
            } catch (error) {
                console.error('Error loading paper details:', error);
                showToast(error.message || 'Failed to load paper details', 'error');
            }
        }

        // Show add to collection modal
        function showAddToCollectionModal() {
            const modalHtml = `
                <div class="modal" id="add-to-collection-modal">
                    <div class="modal__content">
                        <div class="modal__header">
                            <h3>Add to Collection</h3>
                            <button class="button button--icon-only" onclick="closeModal()">
                                <i class="ri-close-line"></i>
                            </button>
                        </div>
                        <div class="modal__body">
                            <div class="form-group">
                                <label for="collection-select">Select Collection</label>
                                <select id="collection-select" class="form-control">
                                    <option value="">Loading collections...</option>
                                </select>
                            </div>
                            <div class="form-group">
                                <label for="new-collection">Or Create New Collection</label>
                                <input type="text" id="new-collection" class="form-control" placeholder="Collection name">
                            </div>
                        </div>
                        <div class="modal__footer">
                            <button class="button button--secondary" onclick="closeModal()">Cancel</button>
                            <button class="button" onclick="addToCollection()">Add</button>
                        </div>
                    </div>
                </div>
            `;

            document.body.insertAdjacentHTML('beforeend', modalHtml);

            // Load collections
            fetch('/book-archive/api/collections.php', {
                headers: { 'X-API-Key': apiKey }
            })
            .then(response => response.json())
            .then(collections => {
                const select = document.getElementById('collection-select');
                select.innerHTML = '<option value="">Select a collection</option>';
                collections.forEach(collection => {
                    select.innerHTML += `<option value="${collection.id}">${collection.name}</option>`;
                });
            })
            .catch(error => {
                console.error('Error loading collections:', error);
                showToast('Failed to load collections', 'error');
            });
        }

        function closeModal() {
            const modal = document.getElementById('add-to-collection-modal');
            if (modal) {
                modal.remove();
            }
        }

        async function addToCollection() {
            const select = document.getElementById('collection-select');
            const newCollection = document.getElementById('new-collection');
            let collectionId = select.value;

            if (newCollection.value) {
                try {
                    const response = await fetch('/book-archive/api/collections.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-API-Key': apiKey
                        },
                        body: JSON.stringify({
                            name: newCollection.value
                        })
                    });
                    const data = await response.json();
                    if (!response.ok) throw new Error(data.error || 'Failed to create collection');
                    collectionId = data.id;
                } catch (error) {
                    showToast(error.message, 'error');
                    return;
                }
            }

            if (!collectionId) {
                showToast('Please select or create a collection', 'error');
                return;
            }

            try {
                const response = await fetch('/book-archive/api/collections.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Key': apiKey
                    },
                    body: JSON.stringify({
                        collection_id: collectionId,
                        paper_id: paperId,
                        type: 'paper',
                        title: document.querySelector('.paper-details__title').textContent,
                        author: document.querySelector('.paper-details__author').textContent,
                        pdf_link: document.querySelector('.paper-details__actions a').href
                    })
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.error || 'Failed to add paper to collection');
                
                showToast('Paper added to collection successfully', 'success');
                closeModal();
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        function generateCitation(paper, format) {
            const authors = paper.authors?.join(', ') || 'Unknown Author';
            const title = paper.title;
            const year = new Date(paper.published).getFullYear() || 'n.d.';
            const journal = paper.journal || 'Unknown Journal';
            const doi = paper.doi || '';

            switch(format) {
                case 'apa':
                    return `${authors} (${year}). ${title}. ${journal}.${doi ? ' https://doi.org/' + doi : ''}`;
                case 'mla':
                    return `${authors}. "${title}." ${journal}, ${year}.${doi ? ' https://doi.org/' + doi : ''}`;
                case 'chicago':
                    return `${authors}. "${title}." ${journal} (${year}).${doi ? ' https://doi.org/' + doi : ''}`;
                case 'ieee':
                    return `${authors}, "${title}," ${journal}, vol. ${paper.volume || 'n.d.'}, no. ${paper.issue || 'n.d.'}, pp. ${paper.pages || 'n.d.'}, ${year}.${doi ? ' https://doi.org/' + doi : ''}`;
                default:
                    return `${authors} (${year}). ${title}. ${journal}.${doi ? ' https://doi.org/' + doi : ''}`;
            }
        }

        function updateCitation() {
            const format = document.getElementById('citation-format').value;
            const paper = window.currentPaper;
            if (paper) {
                document.getElementById('citation-text').textContent = generateCitation(paper, format);
            }
        }

        function copyCitation() {
            const citationText = document.getElementById('citation-text').textContent;
            navigator.clipboard.writeText(citationText).then(() => {
                showToast('Citation copied to clipboard', 'success');
            });
        }

        async function saveToNotes() {
            const citationText = document.getElementById('citation-text').textContent;
            const paperId = window.currentPaper?.id;
            
            if (!paperId) return;

            try {
                const response = await fetch('api/notes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Key': apiKey
                    },
                    body: JSON.stringify({
                        content: `Citation for @journal-${paperId}:\n\n${citationText}`
                    })
                });

                if (!response.ok) throw new Error('Failed to save note');
                
                showToast('Citation saved to notes', 'success');
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        // Load paper details when page loads
        document.addEventListener('DOMContentLoaded', loadPaperDetails);

        // Add event listener for citation format change
        document.getElementById('citation-format').addEventListener('change', updateCitation);
    </script>
</body>
</html> 