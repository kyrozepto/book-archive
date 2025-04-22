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
$user_avatar = "https://via.placeholder.com/40";
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notes | Book Archive</title>
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700&display=swap" rel="stylesheet">
    <link href="https://cdn.jsdelivr.net/npm/remixicon@3.5.0/fonts/remixicon.css" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/styles.css">
    <link href="https://cdn.quilljs.com/1.3.6/quill.snow.css" rel="stylesheet">
    <style>
        .notes-container {
            padding: 2rem;
            max-width: 800px;
            margin: 0 auto;
        }
        .note-editor {
            background: white;
            border-radius: 8px;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
            margin-bottom: 2rem;
        }
        .note-list {
            display: grid;
            gap: 1rem;
        }
        .note-card {
            background: white;
            border-radius: 8px;
            padding: 1.5rem;
            box-shadow: 0 2px 4px rgba(0,0,0,0.1);
        }
        .note-card__content {
            margin-bottom: 1rem;
        }
        .note-card__meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.875rem;
        }
        .mention {
            background: #e2e8f0;
            padding: 0.25rem 0.5rem;
            border-radius: 4px;
            color: #1e293b;
            font-weight: 500;
        }
        .mention.book {
            background: #dbeafe;
            color: #1e40af;
        }
        .mention.journal {
            background: #f0fdf4;
            color: #166534;
        }
    </style>
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
                    <li><a href="notes.php" class="nav-item active"><i class="ri-sticky-note-line"></i><span>Notes</span></a></li>
                    <li><a href="#" class="nav-item"><i class="ri-share-line"></i><span>Shared</span></a></li>
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

            <div class="notes-container">
                <div class="note-editor">
                    <div id="editor"></div>
                    <div class="note-editor__actions">
                        <button class="button" onclick="saveNote()">
                            <i class="ri-save-line"></i> Save Note
                        </button>
                    </div>
                </div>
                <div class="note-list" id="noteList">
                    <!-- Notes will be loaded here -->
                </div>
            </div>
        </main>
    </div>

    <!-- Toast Container -->
    <div id="toast-container"></div>

    <script src="https://cdn.quilljs.com/1.3.6/quill.min.js"></script>
    <script>
        const apiKey = '<?php echo $api_key; ?>';
        let quill;

        // Initialize Quill editor
        document.addEventListener('DOMContentLoaded', () => {
            quill = new Quill('#editor', {
                theme: 'snow',
                modules: {
                    toolbar: [
                        ['bold', 'italic', 'underline', 'strike'],
                        ['blockquote', 'code-block'],
                        [{ 'list': 'ordered'}, { 'list': 'bullet' }],
                        [{ 'indent': '-1'}, { 'indent': '+1' }],
                        [{ 'header': [1, 2, 3, 4, 5, 6, false] }],
                        [{ 'color': [] }, { 'background': [] }],
                        ['clean']
                    ]
                },
                placeholder: 'Write your note here... Use @ to mention books or journals'
            });

            // Load existing notes
            loadNotes();

            // Handle @ mentions
            quill.on('text-change', function() {
                const text = quill.getText();
                const atIndex = text.lastIndexOf('@');
                
                if (atIndex !== -1) {
                    const word = text.substring(atIndex).split(' ')[0];
                    if (word.length > 1) {
                        showMentionSuggestions(word.substring(1));
                    }
                }
            });
        });

        // Show mention suggestions
        function showMentionSuggestions(query) {
            // Fetch books and journals matching the query
            fetch(`api/search.php?q=${query}&type=all`, {
                headers: { 'X-API-Key': apiKey }
            })
            .then(response => response.json())
            .then(data => {
                const suggestions = document.createElement('div');
                suggestions.className = 'mention-suggestions';
                suggestions.innerHTML = data.items.map(item => `
                    <div class="mention-suggestion" onclick="insertMention('${item.mention}')">
                        <i class="ri-${item.type === 'book' ? 'book-2' : 'article'}-line"></i>
                        <span class="mention-suggestion__title">${item.title}</span>
                        <span class="mention-suggestion__id">${item.mention}</span>
                    </div>
                `).join('');
                document.body.appendChild(suggestions);
            });
        }

        // Insert mention into editor
        function insertMention(mention) {
            const range = quill.getSelection();
            if (range) {
                quill.insertText(range.index, mention + ' ', {
                    'mention': true
                });
            }
            document.querySelector('.mention-suggestions')?.remove();
        }

        // Save note
        async function saveNote() {
            const content = quill.root.innerHTML;
            try {
                const response = await fetch('api/notes.php', {
                    method: 'POST',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Key': apiKey
                    },
                    body: JSON.stringify({
                        content: content
                    })
                });

                if (!response.ok) throw new Error('Failed to save note');
                
                showToast('Note saved successfully', 'success');
                quill.setContents([]);
                loadNotes();
            } catch (error) {
                showToast(error.message, 'error');
            }
        }

        // Load notes
        async function loadNotes() {
            try {
                const response = await fetch('api/notes.php', {
                    headers: { 'X-API-Key': apiKey }
                });
                const notes = await response.json();
                
                const noteList = document.getElementById('noteList');
                noteList.innerHTML = notes.map(note => `
                    <div class="note-card">
                        <div class="note-card__content">${note.content}</div>
                        <div class="note-card__meta">
                            <i class="ri-time-line"></i>
                            ${new Date(note.created_at).toLocaleString()}
                        </div>
                    </div>
                `).join('');
            } catch (error) {
                showToast('Failed to load notes', 'error');
            }
        }

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
    </script>
</body>
</html> 