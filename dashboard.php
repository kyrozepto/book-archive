<?php
session_start();
require_once __DIR__ . '/includes/Auth.php';
// require_once __DIR__ . '/includes/Database.php'; // Assuming Database is used elsewhere or in API

$auth = new Auth();

if (!isset($_SESSION['api_key'])) {
    header('Location: index.php');
    exit;
}

$api_key = $_SESSION['api_key'];

// Placeholder for user data - replace with actual data fetch
$user_name = "User Name";
$user_avatar = "https://via.placeholder.com/40"; // Default or fetched avatar
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard | Book Archive</title>
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
                    <li><a href="#" class="nav-item active"><i class="ri-book-2-line"></i><span>All Books</span></a></li>
                    <li><a href="#" class="nav-item"><i class="ri-heart-3-line"></i><span>Favorites</span></a></li>
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
                <div class="search-container">
                    <i class="ri-search-line search-icon"></i>
                    <input type="search" placeholder="Search books by title, author, or ISBN..." class="search-input" id="search-input">
                </div>
                <div class="header-actions">
                     <button class="button button--secondary button--icon-only" aria-label="Add New Book">
                        <i class="ri-add-line"></i>
                    </button>
                     <button class="button button--icon-only" aria-label="Notifications">
                        <i class="ri-notification-3-line"></i>
                        <!-- Add badge here if there are notifications -->
                    </button>
                    <div class="user-menu">
                        <button class="user-menu__toggle" aria-label="User Menu">
                            <img src="<?php echo htmlspecialchars($user_avatar); ?>" alt="User Avatar" class="user-avatar">
                            <i class="ri-arrow-down-s-line"></i>
                        </button>
                        <div class="user-menu__dropdown">
                            <div class="user-menu__info">
                                <span class="user-menu__name"><?php echo htmlspecialchars($user_name); ?></span>
                                <!-- <span class="user-menu__email">user@example.com</span> -->
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
                <div class="content-header">
                    <h2 class="content-title">All Books</h2>
                     <!-- Tabs could go here if needed, or filters -->
                    <div class="tabs">
                        <button class="tab active" data-tab="recent">Recent</button>
                        <button class="tab" data-tab="popular">Popular</button>
                        <button class="tab" data-tab="new">New</button>
                    </div>
                     <!-- Or Add Filter/Sort options -->
                     <!-- <button class="button button--secondary"><i class="ri-filter-3-line"></i> Filter</button> -->
                </div>

                <div class="book-grid" id="book-results">
                    <!-- Loading State -->
                    <div class="loading-placeholder" id="loading-state" style="display: none;">
                        <p>Loading books...</p> <!-- Add a spinner icon here -->
                    </div>
                    <!-- Empty State -->
                    <div class="empty-state" id="empty-state" style="display: none;">
                        <i class="ri-search-eye-line"></i>
                        <p>No books found matching your search.</p>
                        <p class="empty-state__subtext">Try searching for a different title or author.</p>
                    </div>
                    <!-- Book results will be dynamically inserted here -->
                    <!-- Example Book Card Structure (for reference): -->
                    <!--
                    <article class="book-card" data-book-id="OL12345M">
                        <div class="book-card__image-container">
                            <img src="https://covers.openlibrary.org/b/id/8264891-M.jpg" alt="Book Cover" class="book-card__image" onerror="this.src='https://placehold.co/300x450/e2e8f0/94a3b8?text=No+Cover'">
                        </div>
                        <div class="book-card__content">
                            <h3 class="book-card__title">The Lord of the Rings</h3>
                            <p class="book-card__author">J.R.R. Tolkien</p>
                            <div class="book-card__actions">
                                <div class="dropdown">
                                    <button class="button button--icon-only button--subtle dropdown-toggle" aria-label="More actions" onclick="toggleDropdown('OL12345M', this)">
                                        <i class="ri-more-2-fill"></i>
                                    </button>
                                    <div class="dropdown-menu" id="dropdown-OL12345M">
                                        <button class="dropdown-item" onclick="updateStatus('OL12345M', 'wishlist')"><i class="ri-bookmark-line"></i><span>Add to Wishlist</span></button>
                                        <button class="dropdown-item" onclick="updateStatus('OL12345M', 'reading')"><i class="ri-book-open-line"></i><span>Mark as Reading</span></button>
                                        <button class="dropdown-item" onclick="updateStatus('OL12345M', 'read')"><i class="ri-check-line"></i><span>Mark as Read</span></button>
                                        <button class="dropdown-item" onclick="addToCollection('OL12345M')"><i class="ri-folder-add-line"></i><span>Add to Collection</span></button>
                                        <button class="dropdown-item" onclick="shareBook('OL12345M')"><i class="ri-share-line"></i><span>Share</span></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </article>
                    -->
                </div>
            </div>
        </main>
    </div>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <script>
        const searchInput = document.getElementById('search-input');
        const bookGrid = document.getElementById('book-results');
        const loadingState = document.getElementById('loading-state');
        const emptyState = document.getElementById('empty-state');
        const apiKey = '<?php echo $api_key; ?>';
        let searchTimeout;

        // --- Search Functionality ---
        function performSearch() {
            const searchTerm = searchInput.value.trim();
            // Optional: Add minimum search term length
            // if (searchTerm.length < 3) {
            //     bookGrid.innerHTML = ''; // Clear previous results
            //     loadingState.style.display = 'none';
            //     emptyState.style.display = 'none';
            //     return;
            // }

            showLoading();

            fetch(`api/books.php?search=${encodeURIComponent(searchTerm)}`, {
                headers: { 'X-API-Key': apiKey }
            })
            .then(response => {
                if (!response.ok) {
                    throw new Error(`HTTP error! status: ${response.status}`);
                }
                return response.json();
            })
            .then(data => displayBooks(data.docs))
            .catch(error => {
                console.error('Error fetching books:', error);
                showErrorState('Failed to load books. Please try again.');
                showToast('Error searching for books', 'error');
            });
        }

        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            // Debounce search: wait 500ms after user stops typing
            searchTimeout = setTimeout(performSearch, 500);
        });

        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                clearTimeout(searchTimeout); // Prevent duplicate search
                performSearch();
            }
        });

        // --- Display Logic ---
        function showLoading() {
            bookGrid.innerHTML = ''; // Clear previous results
            loadingState.style.display = 'block';
            emptyState.style.display = 'none';
        }

        function showErrorState(message) {
             bookGrid.innerHTML = '';
             loadingState.style.display = 'none';
             emptyState.style.display = 'flex'; // Use flex for alignment
             emptyState.querySelector('p').textContent = message || 'An error occurred.';
             emptyState.querySelector('.empty-state__subtext').style.display = 'none'; // Hide subtext for errors
        }

        function displayBooks(books) {
            loadingState.style.display = 'none';
            bookGrid.innerHTML = ''; // Clear grid before adding new items

            if (books && books.length > 0) {
                emptyState.style.display = 'none';
                books.forEach(book => {
                    const coverId = book.cover_i || '';
                    // Use Medium size covers, fallback to placeholder
                    const coverUrl = coverId ? `https://covers.openlibrary.org/b/id/${coverId}-M.jpg` : 'https://placehold.co/300x450/e2e8f0/94a3b8?text=No+Cover';
                    const author = book.author_name ? book.author_name.join(', ') : 'Unknown Author'; // Join multiple authors
                    const bookKey = book.key.replace('/works/', ''); // Clean key if needed

                    const bookCard = document.createElement('article');
                    bookCard.className = 'book-card';
                    bookCard.setAttribute('data-book-id', bookKey);
                    bookCard.innerHTML = `
                        <div class="book-card__image-container">
                             <img src="${coverUrl}" alt="Cover for ${book.title}" class="book-card__image" loading="lazy" onerror="this.onerror=null; this.src='https://placehold.co/300x450/e2e8f0/94a3b8?text=No+Cover';">
                        </div>
                        <div class="book-card__content">
                            <h3 class="book-card__title" title="${book.title}">${book.title}</h3>
                            <p class="book-card__author">${author}</p>
                            <div class="book-card__actions">
                                <div class="dropdown">
                                    <button class="button button--icon-only button--subtle dropdown-toggle" aria-label="More actions" onclick="toggleDropdown('${bookKey}', this)">
                                        <i class="ri-more-2-fill"></i>
                                    </button>
                                    <div class="dropdown-menu" id="dropdown-${bookKey}">
                                        <button class="dropdown-item" onclick="updateStatus('${bookKey}', 'wishlist', this)"><i class="ri-bookmark-line"></i><span>Add to Wishlist</span></button>
                                        <button class="dropdown-item" onclick="updateStatus('${bookKey}', 'reading', this)"><i class="ri-book-open-line"></i><span>Mark as Reading</span></button>
                                        <button class="dropdown-item" onclick="updateStatus('${bookKey}', 'read', this)"><i class="ri-check-line"></i><span>Mark as Read</span></button>
                                        <div class="dropdown-divider"></div>
                                        <button class="dropdown-item" onclick="addToCollection('${bookKey}')"><i class="ri-folder-add-line"></i><span>Add to Collection</span></button>
                                        <button class="dropdown-item" onclick="shareBook('${bookKey}')"><i class="ri-share-line"></i><span>Share</span></button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    bookGrid.appendChild(bookCard);
                });
            } else {
                loadingState.style.display = 'none';
                emptyState.style.display = 'flex'; // Use flex for alignment
                emptyState.querySelector('p').textContent = 'No books found matching your search.';
                emptyState.querySelector('.empty-state__subtext').style.display = 'block'; // Show subtext
            }
        }

        // --- Status Updates ---
        async function updateStatus(bookId, status, element) {
            const card = element.closest('.book-card');
            const title = card.querySelector('.book-card__title').textContent;
            const author = card.querySelector('.book-card__author').textContent;
            const coverUrl = card.querySelector('.book-card__image').src;

            // Optional: Add loading state to the button/card
            element.disabled = true;
            element.innerHTML = '<i class="ri-loader-4-line spinning"></i><span>Updating...</span>';

            try {
                const response = await fetch('api/items.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Key': apiKey
                    },
                    body: JSON.stringify({
                        type: 'book',
                        book_id: bookId, // Send clean ID
                        title: title,
                        author: author,
                        status: status,
                        cover_url: coverUrl
                    })
                });

                if (!response.ok) {
                    const errorData = await response.json().catch(() => ({})); // Try to parse error details
                    throw new Error(errorData.message || `Failed to update status (HTTP ${response.status})`);
                }

                const result = await response.json();
                showToast(result.message || `Book status updated to ${status}`, 'success');

                // TODO: Update card visually based on new status (e.g., add a badge)
                // Example: remove existing status badges, add new one
                card.querySelectorAll('.status-badge').forEach(badge => badge.remove());
                const statusBadge = document.createElement('span');
                statusBadge.className = `status-badge status-${status}`;
                statusBadge.textContent = status.charAt(0).toUpperCase() + status.slice(1); // Capitalize
                // Decide where to put the badge, e.g., top-right corner
                card.querySelector('.book-card__image-container').appendChild(statusBadge);


                // Reset button and close dropdown after a short delay
                 setTimeout(() => {
                    resetDropdownButton(element, status); // Pass status to potentially keep it highlighted
                    closeAllDropdowns();
                 }, 300);


            } catch (error) {
                console.error('Error updating status:', error);
                showToast(error.message || 'Failed to update status', 'error');
                 resetDropdownButton(element, status); // Reset button on error too
            }
        }

        function resetDropdownButton(buttonElement, status) {
             buttonElement.disabled = false;
             // Restore original icon and text based on status
             let iconClass, text;
             switch (status) {
                 case 'wishlist': iconClass = 'ri-bookmark-line'; text = 'Add to Wishlist'; break;
                 case 'reading': iconClass = 'ri-book-open-line'; text = 'Mark as Reading'; break;
                 case 'read': iconClass = 'ri-check-line'; text = 'Mark as Read'; break;
                 default: iconClass = 'ri-question-line'; text = 'Unknown Action'; // Fallback
             }
             buttonElement.innerHTML = `<i class="${iconClass}"></i><span>${text}</span>`;
        }

        // Placeholder functions for other actions
        function addToCollection(bookId) {
            console.log('Add to collection:', bookId);
            showToast('Add to Collection clicked (not implemented)', 'info');
            closeAllDropdowns();
        }
        function shareBook(bookId) {
            console.log('Share book:', bookId);
            showToast('Share Book clicked (not implemented)', 'info');
             closeAllDropdowns();
        }


        // --- Dropdown Logic ---
        function toggleDropdown(identifier, buttonElement) {
            const dropdownMenu = document.getElementById(`dropdown-${identifier}`);
            const isCurrentlyOpen = dropdownMenu.classList.contains('show');

            closeAllDropdowns(); // Close others first

            if (!isCurrentlyOpen) {
                dropdownMenu.classList.add('show');
                // Position calculation might be needed if menus overflow
                positionDropdown(dropdownMenu, buttonElement);
                 // Add active class to button
                 buttonElement.classList.add('active');
            }
        }

        function closeAllDropdowns() {
            document.querySelectorAll('.dropdown-menu.show').forEach(menu => {
                menu.classList.remove('show');
            });
             document.querySelectorAll('.dropdown-toggle.active').forEach(button => {
                 button.classList.remove('active');
             });
        }

        function positionDropdown(menu, button) {
            // Basic positioning: ensure it doesn't go off-screen right/bottom
             const rect = button.getBoundingClientRect();
             const menuRect = menu.getBoundingClientRect(); // Get initial dimensions

             menu.style.top = `${button.offsetHeight + 4}px`; // Position below button with some gap
             menu.style.left = 'auto'; // Reset left
             menu.style.right = '0'; // Align to the right edge of the button container

             // Check viewport collision (optional but good UX)
             const viewportWidth = window.innerWidth;
             const menuRightEdge = rect.right; // Dropdown aligns right, so its right edge matches button's right edge

             if (menuRightEdge + menuRect.width > viewportWidth) {
                 // If it would overflow right, align left instead (relative to button)
                 // menu.style.right = 'auto';
                 // menu.style.left = '0';
                 // This might need adjustment based on the parent 'dropdown' container position
             }
             // Similar check for bottom collision if needed
        }


        // Close dropdowns when clicking outside
        document.addEventListener('click', function(event) {
            const target = event.target;
            // If click is not on a dropdown toggle or inside a dropdown menu
            if (!target.closest('.dropdown-toggle') && !target.closest('.dropdown-menu')) {
                closeAllDropdowns();
            }
             // Close user menu if click is outside
             if (!target.closest('.user-menu__toggle') && !target.closest('.user-menu__dropdown')) {
                 document.querySelector('.user-menu__dropdown')?.classList.remove('show');
                 document.querySelector('.user-menu__toggle')?.classList.remove('active');
             }
        });

         // --- User Menu Logic ---
        const userMenuToggle = document.querySelector('.user-menu__toggle');
        const userMenuDropdown = document.querySelector('.user-menu__dropdown');

        if (userMenuToggle && userMenuDropdown) {
            userMenuToggle.addEventListener('click', (event) => {
                event.stopPropagation(); // Prevent triggering the global click listener
                const isOpen = userMenuDropdown.classList.toggle('show');
                userMenuToggle.classList.toggle('active', isOpen); // Add active class to toggle
            });
        }

        // --- Toast Notifications ---
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
                 // Remove element after fade out animation completes
                toast.addEventListener('animationend', () => {
                    toast.remove();
                });
            }, duration);
        }

        // --- Initial Load (Example) ---
        document.addEventListener('DOMContentLoaded', () => {
            // Optionally, load initial set of books (e.g., 'recent')
            // performSearch(); // Or call a function to load default view
             console.log("Dashboard loaded");
             // You might want to load 'Recent' books by default if your API supports it
             // fetchDefaultBooks();
        });

    </script>
</body>
</html>