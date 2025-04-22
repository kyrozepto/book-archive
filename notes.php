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
        .note-card__meta {
            display: flex;
            align-items: center;
            gap: 0.5rem;
            color: #64748b;
            font-size: 0.875rem;
            margin-top: 1rem;
        }
        .note-card__actions {
            display: flex;
            gap: 0.5rem;
            margin-left: auto;
        }
        .note-card__button {
            background: none;
            border: none;
            cursor: pointer;
            padding: 0.5rem;
            border-radius: 4px;
            transition: background-color 0.2s;
            display: flex;
            align-items: center;
            justify-content: center;
            width: 32px;
            height: 32px;
        }
        .note-card__edit {
            color: #2563eb;
        }
        .note-card__edit:hover {
            background-color: #dbeafe;
        }
        .note-card__delete {
            color: #dc2626;
        }
        .note-card__delete:hover {
            background-color: #fee2e2;
        }
        .note-card__save {
            color: #16a34a;
        }
        .note-card__save:hover {
            background-color: #dcfce7;
        }
        .note-card__cancel {
            color: #64748b;
        }
        .note-card__cancel:hover {
            background-color: #f1f5f9;
        }
        .note-card__editor {
            margin-top: 1rem;
        }
        .note-card__editor .ql-editor {
            min-height: 100px;
            max-height: 300px;
            overflow-y: auto;
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
                    <li><a href="dashboard.php" class="nav-item"><i class="ri-book-2-line"></i><span>Books</span></a></li>
                    <li><a href="dashboard.php" class="nav-item" id="all-journals-btn"><i class="ri-article-line"></i><span>Journals</span></a></li>
                    <li><a href="notes.php" class="nav-item active"><i class="ri-sticky-note-line"></i><span>Notes</span></a></li>
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
                placeholder: 'Create new note or save your book and journal for reference'
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
                    <div class="note-card" data-note-id="${note.id}">
                        <div class="note-card__content">${note.content}</div>
                        <div class="note-card__meta">
                            <i class="ri-time-line"></i>
                            <span>${new Date(note.created_at).toLocaleString()}</span>
                            <div class="note-card__actions">
                                <button class="note-card__button note-card__edit" onclick="editNote(${note.id})" title="Edit note">
                                    <i class="ri-edit-line"></i>
                                </button>
                                <button class="note-card__button note-card__delete" onclick="deleteNote(${note.id})" title="Delete note">
                                    <i class="ri-delete-bin-line"></i>
                                </button>
                            </div>
                        </div>
                    </div>
                `).join('');
            } catch (error) {
                showToast('Failed to load notes', 'error');
            }
        }

        // Delete note
        async function deleteNote(noteId) {
            if (!confirm('Are you sure you want to delete this note?')) {
                return;
            }

            try {
                const response = await fetch('api/notes.php', {
                    method: 'DELETE',
                    headers: {
                        'Content-Type': 'application/json',
                        'X-API-Key': apiKey
                    },
                    body: JSON.stringify({
                        note_id: noteId
                    })
                });

                const data = await response.json();

                if (response.ok) {
                    showToast('Note deleted successfully', 'success');
                    // Remove the note card from the UI
                    document.querySelector(`.note-card[data-note-id="${noteId}"]`).remove();
                } else {
                    showToast(data.error || 'Failed to delete note', 'error');
                }
            } catch (error) {
                showToast('Failed to delete note', 'error');
            }
        }

        // Edit note
        async function editNote(noteId) {
            const noteCard = document.querySelector(`.note-card[data-note-id="${noteId}"]`);
            const contentDiv = noteCard.querySelector('.note-card__content');
            const actionsDiv = noteCard.querySelector('.note-card__actions');
            
            // Create editor container
            const editorContainer = document.createElement('div');
            editorContainer.className = 'note-card__editor';
            editorContainer.innerHTML = '<div id="editor-' + noteId + '"></div>';
            
            // Create save and cancel buttons
            const saveButton = document.createElement('button');
            saveButton.className = 'note-card__button note-card__save';
            saveButton.innerHTML = '<i class="ri-save-line"></i>';
            saveButton.title = 'Save changes';
            
            const cancelButton = document.createElement('button');
            cancelButton.className = 'note-card__button note-card__cancel';
            cancelButton.innerHTML = '<i class="ri-close-line"></i>';
            cancelButton.title = 'Cancel editing';
            
            // Replace actions with save/cancel
            actionsDiv.innerHTML = '';
            actionsDiv.appendChild(saveButton);
            actionsDiv.appendChild(cancelButton);
            
            // Hide content and show editor
            contentDiv.style.display = 'none';
            noteCard.insertBefore(editorContainer, contentDiv.nextSibling);
            
            // Initialize Quill editor
            const quill = new Quill('#editor-' + noteId, {
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
                }
            });
            
            // Set initial content
            quill.root.innerHTML = contentDiv.innerHTML;
            
            // Add event listeners
            saveButton.onclick = async () => {
                try {
                    const editorContent = quill.root.innerHTML;
                    
                    const response = await fetch('api/notes.php', {
                        method: 'PUT',
                        headers: {
                            'Content-Type': 'application/json',
                            'X-API-Key': apiKey
                        },
                        body: JSON.stringify({
                            note_id: noteId,
                            content: editorContent
                        })
                    });

                    if (!response.ok) {
                        throw new Error('Failed to update note');
                    }

                    const data = await response.json();

                    if (data.success) {
                        showToast('Note updated successfully', 'success');
                        // Update content and restore view
                        contentDiv.innerHTML = editorContent;
                        contentDiv.style.display = 'block';
                        editorContainer.remove();
                        // Restore edit/delete buttons
                        actionsDiv.innerHTML = `
                            <button class="note-card__button note-card__edit" onclick="editNote(${noteId})" title="Edit note">
                                <i class="ri-edit-line"></i>
                            </button>
                            <button class="note-card__button note-card__delete" onclick="deleteNote(${noteId})" title="Delete note">
                                <i class="ri-delete-bin-line"></i>
                            </button>
                        `;
                    } else {
                        throw new Error(data.error || 'Failed to update note');
                    }
                } catch (error) {
                    showToast(error.message, 'error');
                }
            };

            cancelButton.onclick = () => {
                // Restore original view
                contentDiv.style.display = 'block';
                editorContainer.remove();
                
                // Restore edit/delete buttons
                actionsDiv.innerHTML = `
                    <button class="note-card__button note-card__edit" onclick="editNote(${noteId})" title="Edit note">
                        <i class="ri-edit-line"></i>
                    </button>
                    <button class="note-card__button note-card__delete" onclick="deleteNote(${noteId})" title="Delete note">
                        <i class="ri-delete-bin-line"></i>
                    </button>
                `;
            };
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