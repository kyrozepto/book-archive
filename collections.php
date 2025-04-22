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

$user_name = $user['username'];
$user_avatar = "https://ui-avatars.com/api/?name=" . urlencode($user_name) . "&background=random";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Collections | Book Archive</title>
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
                    <li><a href="#" class="nav-item"><i class="ri-article-line"></i><span>All Journals</span></a></li>
                    <li><a href="notes.php" class="nav-item"><i class="ri-sticky-note-line"></i><span>Notes</span></a></li>
                    <li><a href="#" class="nav-item"><i class="ri-share-line"></i><span>Shared</span></a></li>
                </ul>

                <div class="sidebar__section">
                    <h2 class="sidebar__section-title">Collections</h2>
                    <ul id="collections-list">
                        <!-- Collections will be loaded here -->
                    </ul>
                    <li><a href="#" class="nav-item" onclick="showNewCollectionModal()"><i class="ri-folder-add-line"></i><span>New Collection</span></a></li>
                </div>
            </nav>
            <div class="sidebar__footer">
                <a href="logout.php" class="nav-item nav-item--logout"><i class="ri-logout-box-r-line"></i><span>Logout</span></a>
            </div>
        </aside>

        <!-- Main content -->
        <main class="main-content">
            <header class="header">
                <div class="search-container">
                    <i class="ri-search-line search-icon"></i>
                    <input type="search" placeholder="Search your collection..." class="search-input" id="collection-search">
                </div>
                <div class="header-actions">
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
                <div class="collection-grid" id="collection-items">
                    <!-- Collection items will be loaded here -->
                </div>
            </div>
        </main>
    </div>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <!-- New Collection Modal -->
    <div class="modal" id="new-collection-modal" style="display: none;">
        <div class="modal__content">
            <div class="modal__header">
                <h3>Create New Collection</h3>
                <button class="button button--icon-only" onclick="closeNewCollectionModal()">
                    <i class="ri-close-line"></i>
                </button>
            </div>
            <div class="modal__body">
                <div class="form-group">
                    <label for="collection-name">Collection Name</label>
                    <input type="text" id="collection-name" class="form-control" placeholder="Enter collection name">
                </div>
                <div class="form-group">
                    <label for="collection-description">Description (Optional)</label>
                    <textarea id="collection-description" class="form-control" placeholder="Enter collection description"></textarea>
                </div>
            </div>
            <div class="modal__footer">
                <button class="button button--secondary" onclick="closeNewCollectionModal()">Cancel</button>
                <button class="button" onclick="createNewCollection()">Create</button>
            </div>
        </div>
    </div>

    <script>
        const apiKey = '<?php echo $api_key; ?>';

        // Toast notification function
        function showToast(message, type = 'info', duration = 3000) {
            const container = document.getElementById('toast-container');
            if (!container) return;

            const toast = document.createElement('div');
            toast.className = `toast toast--${type}`;

            let iconClass = 'ri-information-line';
            if (type === 'success') iconClass = 'ri-check-line';
            if (type === 'error') iconClass = 'ri-error-warning-line';
            if (type === 'warning') iconClass = 'ri-alert-line';

            toast.innerHTML = `
                <i class="${iconClass} toast__icon"></i>
                <p class="toast__message">${message}</p>
                <button class="toast__close" onclick="this.parentElement.remove()"><i class="ri-close-line"></i></button>
            `;

            container.appendChild(toast);

            setTimeout(() => {
                toast.classList.add('toast--fade-out');
                toast.addEventListener('animationend', () => {
                    toast.remove();
                });
            }, duration);
        }

        // Load collections
        async function loadCollections() {
            try {
                const response = await fetch('api/collections.php', {
                    headers: { 'X-API-Key': apiKey }
                });
                
                if (!response.ok) throw new Error('Failed to load collections');
                
                const collections = await response.json();
                const collectionsList = document.getElementById('collections-list');
                collectionsList.innerHTML = '';
                
                collections.forEach(collection => {
                    const li = document.createElement('li');
                    li.innerHTML = `
                        <a href="#" class="nav-item" onclick="loadCollectionItems('${collection.id}')">
                            <i class="ri-folder-line"></i>
                            <span>${collection.name}</span>
                        </a>
                    `;
                    collectionsList.appendChild(li);
                });
            } catch (error) {
                console.error('Error loading collections:', error);
                showToast('Failed to load collections', 'error');
            }
        }

        // Load collection items
        async function loadCollectionItems(collectionId) {
            try {
                const response = await fetch(`api/collections.php?id=${collectionId}`, {
                    headers: { 'X-API-Key': apiKey }
                });
                
                if (!response.ok) throw new Error('Failed to load collection items');
                
                const items = await response.json();
                const collectionGrid = document.getElementById('collection-items');
                collectionGrid.innerHTML = '';
                
                if (items.length === 0) {
                    collectionGrid.innerHTML = `
                        <div class="empty-state">
                            <i class="ri-inbox-line"></i>
                            <p>No items in this collection</p>
                        </div>
                    `;
                    return;
                }
                
                items.forEach(item => {
                    const itemCard = document.createElement('article');
                    itemCard.className = 'book-card';
                    itemCard.setAttribute(`data-${item.type}-id`, item.id);
                    
                    let coverImage = item.cover_url || 'https://placehold.co/300x450/e2e8f0/94a3b8?text=No+Cover';
                    let typeIcon = item.type === 'book' ? 'ri-book-2-line' : 'ri-article-line';
                    
                    itemCard.innerHTML = `
                        <div class="book-card__image-container">
                            <img src="${coverImage}" alt="${item.title}" class="book-card__image" onerror="this.src='https://placehold.co/300x450/e2e8f0/94a3b8?text=No+Cover'">
                        </div>
                        <div class="book-card__content">
                            <h3 class="book-card__title">${item.title}</h3>
                            <p class="book-card__author">${item.author || 'Unknown Author'}</p>
                            <div class="book-card__meta">
                                <span class="book-card__type"><i class="${typeIcon}"></i> ${item.type}</span>
                                <span class="book-card__status">${item.status}</span>
                            </div>
                            <div class="book-card__actions">
                                <div class="dropdown">
                                    <button class="button button--icon-only button--subtle dropdown-toggle" onclick="event.stopPropagation(); toggleDropdown('${item.id}', this)">
                                        <i class="ri-more-2-fill"></i>
                                    </button>
                                    <div class="dropdown-menu" id="dropdown-${item.id}">
                                        <button class="dropdown-item" onclick="event.stopPropagation(); updateStatus('${item.id}', 'wishlist')">
                                            <i class="ri-bookmark-line"></i><span>Add to Wishlist</span>
                                        </button>
                                        <button class="dropdown-item" onclick="event.stopPropagation(); updateStatus('${item.id}', 'reading')">
                                            <i class="ri-book-open-line"></i><span>Mark as Reading</span>
                                        </button>
                                        <button class="dropdown-item" onclick="event.stopPropagation(); updateStatus('${item.id}', 'read')">
                                            <i class="ri-check-line"></i><span>Mark as Read</span>
                                        </button>
                                        <div class="dropdown-divider"></div>
                                        <button class="dropdown-item" onclick="event.stopPropagation(); removeFromCollection('${item.id}', '${collectionId}')">
                                            <i class="ri-delete-bin-line"></i><span>Remove from Collection</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    
                    // Add click handler for viewing details
                    itemCard.onclick = () => {
                        if (item.type === 'book') {
                            window.location.href = `book-details.php?id=${item.id}`;
                        } else {
                            window.location.href = `journal-details.php?id=${item.id}`;
                        }
                    };
                    
                    collectionGrid.appendChild(itemCard);
                });
            } catch (error) {
                console.error('Error loading collection items:', error);
                showToast('Failed to load collection items', 'error');
            }
        }

        // Collection management functions
        function showNewCollectionModal() {
            document.getElementById('new-collection-modal').style.display = 'block';
        }

        function closeNewCollectionModal() {
            document.getElementById('new-collection-modal').style.display = 'none';
        }

        async function createNewCollection() {
            const name = document.getElementById('collection-name').value;
            const description = document.getElementById('collection-description').value;

            if (!name) {
                showToast('Please enter a collection name', 'error');
                return;
            }

            try {
                const response = await fetch('api/collections.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Key': apiKey
                    },
                    body: JSON.stringify({
                        name,
                        description
                    })
                });

                if (!response.ok) throw new Error('Failed to create collection');

                showToast('Collection created successfully', 'success');
                closeNewCollectionModal();
                loadCollections();
            } catch (error) {
                console.error('Error creating collection:', error);
                showToast('Failed to create collection', 'error');
            }
        }

        async function removeFromCollection(itemId, collectionId) {
            try {
                const response = await fetch(`api/collections.php?id=${collectionId}&item_id=${itemId}`, {
                    method: 'DELETE',
                    headers: { 'X-API-Key': apiKey }
                });

                if (!response.ok) throw new Error('Failed to remove item from collection');

                showToast('Item removed from collection', 'success');
                loadCollectionItems(collectionId);
            } catch (error) {
                console.error('Error removing item from collection:', error);
                showToast('Failed to remove item from collection', 'error');
            }
        }

        // Initialize
        document.addEventListener('DOMContentLoaded', () => {
            loadCollections();
            
            // Search functionality
            const searchInput = document.getElementById('collection-search');
            let searchTimeout;
            
            searchInput.addEventListener('input', () => {
                clearTimeout(searchTimeout);
                searchTimeout = setTimeout(() => {
                    const searchTerm = searchInput.value.trim().toLowerCase();
                    const items = document.querySelectorAll('.book-card');
                    
                    items.forEach(item => {
                        const title = item.querySelector('.book-card__title').textContent.toLowerCase();
                        const author = item.querySelector('.book-card__author').textContent.toLowerCase();
                        const matches = title.includes(searchTerm) || author.includes(searchTerm);
                        item.style.display = matches ? 'block' : 'none';
                    });
                }, 300);
            });
        });
    </script>
</body>
</html> 