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
                    <li><a href="dashboard.php" class="nav-item active"><i class="ri-book-2-line"></i><span>Books</span></a></li>
                    <li><a href="#" class="nav-item" id="all-journals-btn"><i class="ri-article-line"></i><span>Journals</span></a></li>
                    <li><a href="notes.php" class="nav-item"><i class="ri-sticky-note-line"></i><span>Notes</span></a></li>
                </ul>
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
                    <input type="search" placeholder="Search books by title, author, or ISBN" class="search-input" id="search-input">
                </div>
                <div class="header-actions">
                    <a href="logout.php" class="nav-item nav-item--logout"><i class="ri-logout-box-r-line"></i> Logout</a>
                </div>
            </header>

            <div class="content-area">
                <div class="content-header">
                    <h2 class="content-title">Books</h2>
                </div>

                <div class="book-grid" id="book-results">
                    <div class="loading-placeholder" id="loading-state" style="display: none;">
                        <p>Loading books...</p>
                    </div>
                    <!-- Empty State -->
                    <div class="empty-state" id="empty-state" style="display: none;">
                        <i class="ri-search-eye-line"></i>
                        <p>No books found matching your search.</p>
                        <p class="empty-state__subtext">Try searching for a different title or author.</p>
                    </div>
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
        let currentMode = 'books'

        document.getElementById('all-journals-btn').addEventListener('click', function(e) {
            e.preventDefault();
            currentMode = 'journals';
            document.querySelector('.nav-item.active').classList.remove('active');
            this.classList.add('active');
            document.querySelector('.content-title').textContent = 'Journals';
            searchInput.placeholder = 'Search papers by title, author, or arXiv ID';
            bookGrid.innerHTML = '';
            showEmptyState();
        });

        // Add event listener for Books button
        document.querySelector('.nav-item:first-child').addEventListener('click', function(e) {
            e.preventDefault();
            currentMode = 'books';
            document.querySelector('.nav-item.active').classList.remove('active');
            this.classList.add('active');
            document.querySelector('.content-title').textContent = 'Books';
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
                fetch(`/book-archive/api/books.php?search=${encodeURIComponent(searchTerm)}`, {
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
                                        <button class="button" onclick="window.location.href='book-details.php?id=${bookId}'">
                                            <i class="ri-eye-line"></i> View Details
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