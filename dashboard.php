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
$user = $auth->getCurrentUser();

if (!$user) {
    header('Location: index.php');
    exit;
}

$user_name = $user['username'];
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
                    <li><a href="#" class="nav-item" id="all-journals-btn"><i class="ri-article-line"></i><span>All Journals</span></a></li>
                    <li><a href="notes.php" class="nav-item"><i class="ri-sticky-note-line"></i><span>Notes</span></a></li>
                    <li><a href="#" class="nav-item"><i class="ri-share-line"></i><span>Shared</span></a></li>
                </ul>

                <div class="sidebar__section">
                    <h2 class="sidebar__section-title">Collections</h2>
                    <ul>
                        <li><a href="collections.php" class="nav-item"><i class="ri-folder-line"></i><span>My Collections</span></a></li>
                        <li><a href="#" class="nav-item" onclick="showNewCollectionModal()"><i class="ri-folder-add-line"></i><span>New Collection</span></a></li>
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
        let currentTab = 'recent';
        let currentMode = 'books'; // 'books' or 'journals'

        // Add event listener for All Journals button
        document.getElementById('all-journals-btn').addEventListener('click', function(e) {
            e.preventDefault();
            currentMode = 'journals';
            document.querySelector('.nav-item.active').classList.remove('active');
            this.classList.add('active');
            document.querySelector('.content-title').textContent = 'All Journals';
            searchInput.placeholder = 'Search papers by title, author, or arXiv ID...';
            bookGrid.innerHTML = '';
            showEmptyState();
        });

        // Add event listener for All Books button
        document.querySelector('.nav-item:first-child').addEventListener('click', function(e) {
            e.preventDefault();
            currentMode = 'books';
            document.querySelector('.nav-item.active').classList.remove('active');
            this.classList.add('active');
            document.querySelector('.content-title').textContent = 'All Books';
            searchInput.placeholder = 'Search books by title, author, or ISBN...';
            bookGrid.innerHTML = '';
            showEmptyState();
        });

        // --- Search Functionality ---
        function performSearch() {
            const searchTerm = searchInput.value.trim();
            if (searchTerm.length < 2) {
                showErrorState('Please enter at least 2 characters to search');
                return;
            }

            showLoading();

            if (currentMode === 'books') {
                // Existing book search code
            fetch(`/book-archive/api/books.php?search=${encodeURIComponent(searchTerm)}&tab=${currentTab}`, {
                headers: { 
                    'X-API-Key': apiKey,
                    'Content-Type': 'application/json'
                }
            })
            .then(response => {
                if (!response.ok) {
                    return response.json().then(errorData => {
                        throw new Error(errorData.details || `Error: ${response.status}`);
                    });
                }
                return response.json();
            })
            .then(data => {
                if (data.error) {
                    throw new Error(data.error);
                }
                if (!data.docs || data.docs.length === 0) {
                    showEmptyState();
                    return;
                }
                displayBooks(data.docs);
            })
            .catch(error => {
                console.error('Error fetching books:', error);
                showErrorState(error.message || 'Failed to load books. Please try again.');
                showToast(error.message || 'Error searching for books', 'error');
            });
            } else {
                // Journal search using our API endpoint
                fetch(`/book-archive/api/journals.php?search=${encodeURIComponent(searchTerm)}&start=0&max_results=20`, {
                    headers: { 
                        'X-API-Key': apiKey,
                        'Content-Type': 'application/json'
                    }
                })
                .then(response => {
                    if (!response.ok) {
                        return response.json().then(errorData => {
                            throw new Error(errorData.error || `Error: ${response.status}`);
                        });
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    if (!data.papers || data.papers.length === 0) {
                        showEmptyState();
                        return;
                    }
                    displayPapers(data.papers);
                })
                .catch(error => {
                    console.error('Error fetching papers:', error);
                    showErrorState(error.message || 'Failed to load papers. Please try again.');
                    showToast(error.message || 'Error searching for papers', 'error');
                });
            }
        }

        function displayPapers(papers) {
            loadingState.style.display = 'none';
            bookGrid.innerHTML = '';

            papers.forEach(paper => {
                const paperCard = document.createElement('article');
                paperCard.className = 'book-card';
                paperCard.setAttribute('data-paper-id', paper.id);
                paperCard.onclick = () => handleBookCardClick(paper.id);
                paperCard.innerHTML = `
                    <div class="book-card__content">
                        <h3 class="book-card__title" title="${paper.title}">${paper.title}</h3>
                        <p class="book-card__author">${paper.authors.join(', ')}</p>
                        <p class="book-card__summary">${paper.summary}</p>
                        <div class="book-card__actions">
                            <div class="dropdown">
                                <button class="button button--icon-only button--subtle dropdown-toggle" aria-label="More actions" onclick="event.stopPropagation(); toggleDropdown('${paper.id}', this)">
                                    <i class="ri-more-2-fill"></i>
                                </button>
                                <div class="dropdown-menu" id="dropdown-${paper.id}">
                                    <a href="${paper.pdfLink}" class="dropdown-item" target="_blank" onclick="event.stopPropagation();"><i class="ri-file-pdf-line"></i><span>View PDF</span></a>
                                    <button class="dropdown-item" onclick="event.stopPropagation(); addToCollection('${paper.id}')"><i class="ri-folder-add-line"></i><span>Add to Collection</span></button>
                                    <div class="dropdown-divider"></div>
                                    <button class="dropdown-item" onclick="event.stopPropagation(); showPaperDetails('${paper.id}')"><i class="ri-eye-line"></i><span>View Details</span></button>
                                </div>
                            </div>
                        </div>
                    </div>
                `;
                bookGrid.appendChild(paperCard);
            });
        }

        function showPaperDetails(paperId) {
            // Find the paper in the current results
            const paperCard = document.querySelector(`[data-paper-id="${paperId}"]`);
            const originalContent = paperCard.innerHTML;
            paperCard.innerHTML = '<div class="loading-placeholder"><p>Loading paper details...</p></div>';

            // Fetch detailed paper information from our API
            fetch(`/book-archive/api/journals.php?search=${paperId}&max_results=1`, {
                headers: { 
                    'X-API-Key': apiKey,
                    'Content-Type': 'application/json'
                }
            })
                .then(response => {
                    if (!response.ok) {
                        throw new Error(`HTTP error! status: ${response.status}`);
                    }
                    return response.json();
                })
                .then(data => {
                    if (data.error) {
                        throw new Error(data.error);
                    }
                    if (!data.papers || data.papers.length === 0) {
                        throw new Error('Paper not found');
                    }

                    const paper = data.papers[0];
                    const detailsHtml = `
                        <div class="book-details">
                            <div class="book-details__content">
                                <div class="book-details__info">
                                    <h1 class="book-details__title">${paper.title}</h1>
                                    <p class="book-details__author">${paper.authors.join(', ')}</p>
                                    <p class="book-details__meta">Published: ${new Date(paper.published).toLocaleDateString()}</p>
                                    <p class="book-details__meta">Last Updated: ${new Date(paper.updated).toLocaleDateString()}</p>
                                    <div class="book-details__description">
                                        ${paper.summary}
                                    </div>
                                    <div class="book-details__actions">
                                        <a href="${paper.pdfLink}" class="button" target="_blank">
                                            <i class="ri-file-pdf-line"></i> View PDF
                                        </a>
                                        <button class="button button--secondary" onclick="addToCollection('${paper.id}')">
                                            <i class="ri-folder-add-line"></i> Add to Collection
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    paperCard.innerHTML = detailsHtml;
                })
                .catch(error => {
                    console.error('Error fetching paper details:', error);
                    paperCard.innerHTML = originalContent;
                    showToast('Failed to load paper details', 'error');
                });
        }

        // Update existing event listeners
        searchInput.addEventListener('input', () => {
            clearTimeout(searchTimeout);
            searchTimeout = setTimeout(performSearch, 500);
        });

        searchInput.addEventListener('keypress', (e) => {
            if (e.key === 'Enter') {
                clearTimeout(searchTimeout);
                performSearch();
            }
        });

        // --- Display Logic ---
        function showLoading() {
            bookGrid.innerHTML = '';
            loadingState.style.display = 'block';
            emptyState.style.display = 'none';
        }

        function showErrorState(message) {
            bookGrid.innerHTML = '';
            loadingState.style.display = 'none';
            emptyState.style.display = 'flex';
            emptyState.querySelector('p').textContent = message;
            emptyState.querySelector('.empty-state__subtext').style.display = 'none';
        }

        function showEmptyState() {
            bookGrid.innerHTML = '';
            loadingState.style.display = 'none';
            emptyState.style.display = 'flex';
            emptyState.querySelector('p').textContent = 'No books found matching your search.';
            emptyState.querySelector('.empty-state__subtext').style.display = 'block';
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
                    bookCard.onclick = () => handleBookCardClick(bookKey);
                    bookCard.innerHTML = `
                        <div class="book-card__image-container">
                             <img src="${coverUrl}" alt="Cover for ${book.title}" class="book-card__image" loading="lazy" onerror="this.onerror=null; this.src='https://placehold.co/300x450/e2e8f0/94a3b8?text=No+Cover';">
                        </div>
                        <div class="book-card__content">
                            <h3 class="book-card__title" title="${book.title}">${book.title}</h3>
                            <p class="book-card__author">${author}</p>
                            <div class="book-card__actions">
                                <div class="dropdown">
                                    <button class="button button--icon-only button--subtle dropdown-toggle" aria-label="More actions" onclick="event.stopPropagation(); toggleDropdown('${bookKey}', this)">
                                        <i class="ri-more-2-fill"></i>
                                    </button>
                                    <div class="dropdown-menu" id="dropdown-${bookKey}">
                                        <button class="dropdown-item" onclick="event.stopPropagation(); updateStatus('${bookKey}', 'wishlist', this)"><i class="ri-bookmark-line"></i><span>Add to Wishlist</span></button>
                                        <button class="dropdown-item" onclick="event.stopPropagation(); updateStatus('${bookKey}', 'reading', this)"><i class="ri-book-open-line"></i><span>Mark as Reading</span></button>
                                        <button class="dropdown-item" onclick="event.stopPropagation(); updateStatus('${bookKey}', 'read', this)"><i class="ri-check-line"></i><span>Mark as Read</span></button>
                                        <div class="dropdown-divider"></div>
                                        <button class="dropdown-item" onclick="event.stopPropagation(); showBookDetails('${bookKey}')"><i class="ri-eye-line"></i><span>View Details</span></button>
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

        // Add after the displayBooks function
        function showBookDetails(bookId) {
            // Show loading state
            const bookCard = document.querySelector(`[data-book-id="${bookId}"]`);
            const originalContent = bookCard.innerHTML;
            bookCard.innerHTML = '<div class="loading-placeholder"><p>Loading book details...</p></div>';

            // Fetch book details from Open Library API
            fetch(`https://openlibrary.org/works/${bookId}.json`)
                .then(response => response.json())
                .then(data => {
                    // Create book details view
                    const detailsHtml = `
                        <div class="book-details">
                            <div class="book-details__header">
                                <button class="button button--icon-only" onclick="closeBookDetails('${bookId}')">
                                    <i class="ri-arrow-left-line"></i>
                                </button>
                                <h2>${data.title}</h2>
                            </div>
                            <div class="book-details__content">
                                <div class="book-details__cover">
                                    <img src="https://covers.openlibrary.org/b/id/${data.covers?.[0] || ''}-L.jpg" 
                                         alt="Cover for ${data.title}"
                                         onerror="this.src='https://placehold.co/300x450/e2e8f0/94a3b8?text=No+Cover'">
                                </div>
                                <div class="book-details__info">
                                    <h3>${data.title}</h3>
                                    <p class="book-details__author">${data.authors?.map(a => a.author.key).join(', ') || 'Unknown Author'}</p>
                                    <p class="book-details__description">${data.description?.value || data.description || 'No description available'}</p>
                                    <div class="book-details__actions">
                                        <button class="button" onclick="showAddToCollectionModal('${bookId}')">
                                            <i class="ri-folder-add-line"></i> Add to Collection
                                        </button>
                                    </div>
                                </div>
                            </div>
                        </div>
                    `;
                    bookCard.innerHTML = detailsHtml;
                })
                .catch(error => {
                    console.error('Error fetching book details:', error);
                    bookCard.innerHTML = originalContent;
                    showToast('Failed to load book details', 'error');
                });
        }

        function closeBookDetails(bookId) {
            const bookCard = document.querySelector(`[data-book-id="${bookId}"]`);
            // Restore original book card content
            displayBooks([{
                key: bookId,
                title: bookCard.querySelector('h2').textContent,
                author_name: [bookCard.querySelector('.book-details__author').textContent],
                cover_i: bookCard.querySelector('img').src.match(/\/b\/id\/(\d+)-/)?.[1]
            }]);
        }

        function showAddToCollectionModal(bookId) {
            // Create modal HTML
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
                            <button class="button" onclick="addToCollection('${bookId}')">Add</button>
                        </div>
                    </div>
                </div>
            `;

            // Add modal to document
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

        async function addToCollection(bookId) {
            const select = document.getElementById('collection-select');
            const newCollection = document.getElementById('new-collection');
            let collectionId = select.value;

            // If new collection name is provided, create it first
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

            // Add book to collection
            try {
                const response = await fetch('/book-archive/api/collections.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Key': apiKey
                    },
                    body: JSON.stringify({
                        collection_id: collectionId,
                        book_id: bookId
                    })
                });
                const data = await response.json();
                if (!response.ok) throw new Error(data.error || 'Failed to add book to collection');
                
                showToast('Book added to collection successfully', 'success');
                closeModal();
                closeBookDetails(bookId);
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        // Handle book card click
        function handleBookCardClick(id) {
            const card = document.querySelector(`[data-paper-id="${id}"]`) || document.querySelector(`[data-book-id="${id}"]`);
            if (!card) return;

            if (card.hasAttribute('data-paper-id')) {
                window.location.href = `journal-details.php?id=${id}`;
            } else {
                window.location.href = `book-details.php?id=${id}`;
            }
        }

    </script>
</body>
</html>