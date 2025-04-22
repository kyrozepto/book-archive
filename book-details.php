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

$book_id = $_GET['id'] ?? null;
if (!$book_id) {
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
    <title>Book Details | Book Archive</title>
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
                    <li><a href="dashboard.php" class="nav-item"><i class="ri-book-2-line"></i><span>Books</span></a></li>
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
                <div class="header-actions">
                    <a href="dashboard.php" class="button button--secondary">
                        <i class="ri-arrow-left-line"></i> Back to Dashboard
                    </a>
                </div>
                <div class="header-actions">
                    <a href="logout.php" class="nav-item nav-item--logout"><i class="ri-logout-box-r-line"></i> Logout</a>
                </div>
            </header>

            <div class="content-area">
                <div class="book-details-container" id="book-details">
                    <!-- Loading state -->
                    <div class="loading-placeholder">
                        <p>Loading book details...</p>
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

                <!-- Author Photos Section -->
                <div class="author-photos-section" id="author-photos-section" style="display: none;">
                    <div class="author-photos-section__header">
                        <h3>Author Photos</h3>
                    </div>
                    <div class="author-photos-section__content">
                        <div class="author-photos-grid" id="author-photos-grid">
                            <!-- Author photos will be loaded here -->
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

    .author-photos-section {
        background: white;
        border-radius: 8px;
        padding: 1.5rem;
        margin-top: 2rem;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .author-photos-section__header {
        margin-bottom: 1rem;
    }

    .author-photos-grid {
        display: grid;
        grid-template-columns: repeat(auto-fill, minmax(150px, 1fr));
        gap: 1.5rem;
    }

    .author-photo-card {
        display: flex;
        flex-direction: column;
        align-items: center;
        gap: 0.5rem;
    }

    .author-photo {
        width: 150px;
        height: 150px;
        border-radius: 8px;
        object-fit: cover;
        box-shadow: 0 2px 4px rgba(0,0,0,0.1);
    }

    .author-name {
        font-size: 0.875rem;
        text-align: center;
        color: #4b5563;
    }
    </style>

    <script>
        const apiKey = '<?php echo $api_key; ?>';
        const bookId = '<?php echo htmlspecialchars($book_id); ?>';

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

        // Fetch book details
        async function loadBookDetails() {
            try {
                let book = null;
                
                // First try to get the book from our API
                const ourApiResponse = await fetch(`/book-archive/api/books.php?search=${encodeURIComponent(bookId)}&max_results=1`, {
                    headers: { 
                        'X-API-Key': apiKey,
                        'Content-Type': 'application/json'
                    }
                });
                
                if (ourApiResponse.ok) {
                    const ourData = await ourApiResponse.json();
                    if (ourData.books && ourData.books.length > 0) {
                        book = ourData.books[0];
                    }
                }

                // If not found in our database, try Open Library
                if (!book) {
                    const olResponse = await fetch(`https://openlibrary.org/works/${bookId}.json`);
                    if (!olResponse.ok) {
                        throw new Error('Failed to fetch book details from Open Library');
                    }
                    const olData = await olResponse.json();
                    
                    // Get author names and photos from Open Library
                    let authorNames = 'Unknown Author';
                    let authorPhotos = [];
                    
                    if (olData.authors && olData.authors.length > 0) {
                        // Fetch author details for each author
                        const authorPromises = olData.authors.map(async (author) => {
                            const authorKey = author.author.key;
                            if (!authorKey) return { name: 'Unknown Author', photo: null };
                            
                            try {
                                const authorResponse = await fetch(`https://openlibrary.org${authorKey}.json`);
                                if (!authorResponse.ok) return { name: 'Unknown Author', photo: null };
                                
                                const authorData = await authorResponse.json();
                                const photoKey = authorKey.split('/').pop();
                                return {
                                    name: authorData.name || 'Unknown Author',
                                    photo: `https://covers.openlibrary.org/a/olid/${photoKey}-L.jpg`
                                };
                            } catch (error) {
                                console.error('Error fetching author details:', error);
                                return { name: 'Unknown Author', photo: null };
                            }
                        });
                        
                        const authorDetails = await Promise.all(authorPromises);
                        authorNames = authorDetails.map(a => a.name).join(', ');
                        authorPhotos = authorDetails;
                    }
                    
                    // Transform Open Library data to our format
                    book = {
                        id: bookId,
                        title: olData.title,
                        author: authorNames,
                        authorPhotos: authorPhotos,
                        description: olData.description?.value || olData.description || 'No description available',
                        cover_url: olData.covers?.[0] ? `https://covers.openlibrary.org/b/id/${olData.covers[0]}-L.jpg` : null,
                        published: olData.first_publish_date || null
                    };
                }

                if (!book) {
                    throw new Error('Book not found');
                }

                window.currentBook = book;
                
                // Show citation section
                document.getElementById('citation-section').style.display = 'block';
                updateCitation();

                // Show author photos section if available
                if (book.authorPhotos && book.authorPhotos.length > 0) {
                    document.getElementById('author-photos-section').style.display = 'block';
                    const authorPhotosGrid = document.getElementById('author-photos-grid');
                    authorPhotosGrid.innerHTML = book.authorPhotos.map(author => `
                        <div class="author-photo-card">
                            <img src="${author.photo}" 
                                 alt="Photo of ${author.name}" 
                                 class="author-photo"
                                 onerror="this.src='https://placehold.co/150x150/e2e8f0/94a3b8?text=No+Photo'">
                            <span class="author-name">${author.name}</span>
                        </div>
                    `).join('');
                }

                // Create book details HTML
                const detailsHtml = `
                    <div class="book-details">
                        <div class="book-details__content">
                            <div class="book-details__cover">
                                <img src="${book.cover_url || 'https://placehold.co/300x450/e2e8f0/94a3b8?text=No+Cover'}" 
                                     alt="Cover for ${book.title}"
                                     onerror="this.src='https://placehold.co/300x450/e2e8f0/94a3b8?text=No+Cover'">
                            </div>
                            <div class="book-details__info">
                                <h1 class="book-details__title">${book.title}</h1>
                                <p class="book-details__author">${book.author}</p>
                                <div class="book-details__description">
                                    ${book.description || 'No description available'}
                                </div>
                                <div class="book-details__actions">
                                </div>
                            </div>
                        </div>
                    </div>
                `;

                document.getElementById('book-details').innerHTML = detailsHtml;
            } catch (error) {
                console.error('Error loading book details:', error);
                showToast(error.message || 'Failed to load book details', 'error');
            }
        }

        function generateCitation(book, format) {
            const authors = book.author || 'Unknown Author';
            const title = book.title;
            const year = new Date(book.published).getFullYear() || 'n.d.';
            const publisher = book.publisher === 'Unknown Publisher' ? '' : book.publisher;
            const isbn = book.isbn || '';

            switch(format) {
                case 'apa':
                    return `${authors} (${year}). ${title}.${publisher ? ' ' + publisher + '.' : ''}${isbn ? ' ISBN: ' + isbn : ''}`;
                case 'mla':
                    return `${authors}. "${title}."${publisher ? ' ' + publisher + ',' : ''} ${year}.${isbn ? ' ISBN: ' + isbn : ''}`;
                case 'chicago':
                    return `${authors}. ${title}.${publisher ? ' ' + publisher + ',' : ''} ${year}.${isbn ? ' ISBN: ' + isbn : ''}`;
                case 'ieee':
                    return `${authors}, "${title},"${publisher ? ' ' + publisher + ',' : ''} ${year}.${isbn ? ' ISBN: ' + isbn : ''}`;
                default:
                    return `${authors} (${year}). ${title}.${publisher ? ' ' + publisher + '.' : ''}${isbn ? ' ISBN: ' + isbn : ''}`;
            }
        }

        function updateCitation() {
            const format = document.getElementById('citation-format').value;
            const book = window.currentBook;
            if (book) {
                document.getElementById('citation-text').textContent = generateCitation(book, format);
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
            const book = window.currentBook;
            
            if (!book || !book.id) {
                showToast('Book information not available', 'error');
                return;
            }

            try {
                const response = await fetch('/book-archive/api/notes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Key': apiKey
                    },
                    body: JSON.stringify({
                        content: `Citation for @book-${book.id}:\n\n${citationText}`
                    })
                });

                if (!response.ok) {
                    const data = await response.json();
                    throw new Error(data.error || 'Failed to save note');
                }
                
                showToast('Citation saved to notes', 'success');
            } catch (error) {
                console.error('Error saving note:', error);
                showToast(error.message, 'error');
            }
        }

        // Load book details when page loads
        document.addEventListener('DOMContentLoaded', loadBookDetails);

        // Add event listener for citation format change
        document.getElementById('citation-format').addEventListener('change', updateCitation);
    </script>
</body>
</html> 