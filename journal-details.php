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
                    <li><a href="dashboard.php" class="nav-item"><i class="ri-book-2-line"></i><span>Books</span></a></li>
                    <li><a href="#" class="nav-item active"><i class="ri-article-line"></i><span>Journals</span></a></li>
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

        function generateCitation(paper, format) {
            // Format author names according to citation style
            const formatAuthorNames = (authors, format) => {
                if (!authors || authors.length === 0) return 'Unknown Author';
                
                if (format === 'apa') {
                    // APA: Last, F. M., & Last, F. M.
                    return authors.map(author => {
                        const parts = author.split(' ');
                        const lastName = parts.pop();
                        const initials = parts.map(p => p[0] + '.').join(' ');
                        return `${lastName}, ${initials}`;
                    }).join(', & ');
                } else if (format === 'mla' || format === 'chicago') {
                    // MLA/Chicago: Last, First M., and First M. Last
                    return authors.map((author, index) => {
                        const parts = author.split(' ');
                        const lastName = parts.pop();
                        const firstName = parts.join(' ');
                        if (index === authors.length - 1 && authors.length > 1) {
                            return `and ${firstName} ${lastName}`;
                        }
                        return `${lastName}, ${firstName}`;
                    }).join(', ');
                } else {
                    // IEEE: F. M. Last, F. M. Last
                    return authors.map(author => {
                        const parts = author.split(' ');
                        const lastName = parts.pop();
                        const initials = parts.map(p => p[0] + '.').join(' ');
                        return `${initials} ${lastName}`;
                    }).join(', ');
                }
            };

            const authors = formatAuthorNames(paper.authors, format);
            const title = paper.title;
            const year = new Date(paper.published).getFullYear() || 'n.d.';
            const arxivId = paper.id || '';

            switch(format) {
                case 'apa':
                    return `${authors} (${year}). ${title}. arXiv preprint arXiv:${arxivId}`;
                case 'mla':
                    return `${authors}. "${title}." arXiv, ${year}, arXiv:${arxivId}`;
                case 'chicago':
                    return `${authors}. "${title}." arXiv, ${year}, arXiv:${arxivId}`;
                case 'ieee':
                    return `${authors}, "${title}," arXiv, ${year}, arXiv:${arxivId}`;
                default:
                    return `${authors} (${year}). ${title}. arXiv preprint arXiv:${arxivId}`;
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