document.addEventListener('DOMContentLoaded', function () {
    const videoContainer = document.querySelector('.video-container');
    if (!videoContainer) return;

    const videoElement = document.querySelector('video');
    if (!videoElement) return;

    const videoId = videoElement.dataset.videoId;
    if (!videoId) return;

    initComments(videoId);
});

function initComments(videoId) {
    const commentsSection = document.querySelector('.comments-section');
    if (!commentsSection) return;

    commentsSection.innerHTML = '';

    const commentsTitle = document.createElement('h3');
    commentsTitle.textContent = 'Загрузка комментариев...';
    commentsSection.appendChild(commentsTitle);

    if (isLoggedIn) {
        const commentForm = createCommentForm(videoId);
        commentsSection.appendChild(commentForm);
    }

    const commentsList = document.createElement('div');
    commentsList.id = 'comments-list';
    commentsSection.appendChild(commentsList);

    loadComments(videoId);
}

function createCommentForm(videoId) {
    const form = document.createElement('form');
    form.className = 'comment-form';

    const formHeader = document.createElement('div');
    formHeader.className = 'comment-form-header';

    const avatarContainer = document.createElement('div');
    avatarContainer.className = 'comment-form-avatar';

    const avatar = document.createElement('img');
    avatar.src = `/api/get_avatar.php?user_id=${userId}&size=40`;
    avatar.alt = 'Ваш аватар';

    avatarContainer.appendChild(avatar);
    formHeader.appendChild(avatarContainer);

    const inputWrapper = document.createElement('div');
    inputWrapper.className = 'comment-input-wrapper';

    const textarea = document.createElement('textarea');
    textarea.className = 'comment-input';
    textarea.placeholder = 'Напишите комментарий...';
    textarea.required = true;

    inputWrapper.appendChild(textarea);
    formHeader.appendChild(inputWrapper);

    form.appendChild(formHeader);

    const formActions = document.createElement('div');
    formActions.className = 'comment-form-actions';

    const submitButton = document.createElement('button');
    submitButton.type = 'submit';
    submitButton.className = 'comment-submit-btn';
    submitButton.textContent = 'Отправить';
    submitButton.disabled = true;

    formActions.appendChild(submitButton);
    form.appendChild(formActions);

    textarea.addEventListener('input', function () {
        submitButton.disabled = textarea.value.trim() === '';
    });

    form.addEventListener('submit', function (e) {
        e.preventDefault();

        const commentText = textarea.value.trim();
        if (!commentText) return;

        submitButton.disabled = true;
        submitButton.textContent = 'Отправка...';

        addComment(videoId, commentText)
            .then(response => response.json())
            .then(response => {
                if (response.success) {
                    textarea.value = '';

                    const commentsList = document.getElementById('comments-list');
                    if (commentsList) {
                        response.comment.is_author = 1;

                        const commentElement = createCommentElement(response.comment);
                        const emptyMessage = commentsList.querySelector('.no-comments-message');
                        if (emptyMessage) {
                            emptyMessage.remove();
                        }

                        if (commentsList.firstChild) {
                            commentsList.insertBefore(commentElement, commentsList.firstChild);
                        } else {
                            commentsList.appendChild(commentElement);
                        }

                        updateCommentsCount();
                    }

                    UIUtils.showNotification('Комментарий успешно добавлен', 'success');
                } else {
                    UIUtils.showNotification('Не удалось добавить комментарий', 'error');
                }

                submitButton.disabled = false;
                submitButton.textContent = 'Отправить';
            })
            .catch(error => {
                UIUtils.logError('добавлении комментария', error);
                UIUtils.showNotification('Произошла ошибка при добавлении комментария', 'error');

                submitButton.disabled = false;
                submitButton.textContent = 'Отправить';
            });
    });

    return form;
}

function loadComments(videoId, offset = 0) {
    const commentsList = document.getElementById('comments-list');

    if (offset === 0) {
        commentsList.innerHTML = '<div class="loading-comments">Загрузка комментариев...</div>';
    } else {
        const loadingElement = document.createElement('div');
        loadingElement.className = 'loading-comments';
        loadingElement.textContent = 'Загрузка комментариев...';
        commentsList.appendChild(loadingElement);
    }

    fetch(`/api/comments.php?video_id=${videoId}&offset=${offset}&limit=10`)
        .then(response => response.json())
        .then(data => {
            const loadingElement = commentsList.querySelector('.loading-comments');
            if (loadingElement) {
                loadingElement.remove();
            }

            if (offset === 0 && data.comments.length === 0) {
                const emptyMessage = document.createElement('div');
                emptyMessage.className = 'no-comments-message';
                emptyMessage.textContent = 'Нет комментариев. Будьте первым, кто оставит комментарий!';
                commentsList.appendChild(emptyMessage);
                return;
            }

            data.comments.forEach(comment => {
                if (comment.user_id == userId && !comment.is_author) {
                    comment.is_author = 1;
                }

                const commentElement = createCommentElement(comment);
                commentsList.appendChild(commentElement);

                if (comment.replies_count > 0) {
                    const repliesButton = commentElement.querySelector('.comment-replies-btn');
                    // loadReplies(comment.comment_id, repliesButton, 0);

                    repliesButton.textContent = `Ответы (${comment.replies_count})`;
                }
            });
        })
        .catch(error => {
            UIUtils.logError('загрузке комментариев', error);
            commentsList.innerHTML = '<div class="comments-error">Не удалось загрузить комментарии</div>';
        });
}

function createCommentElement(comment) {
    const commentItem = document.createElement('div');
    commentItem.className = 'comment-item';
    commentItem.id = `comment-${comment.comment_id}`;
    commentItem.dataset.commentId = comment.comment_id;
    commentItem.dataset.content = comment.content;

    if (comment.parent_comment_id) {
        commentItem.classList.add('comment-reply');
        commentItem.dataset.parentId = comment.parent_comment_id;
    }

    const mainContent = document.createElement('div');
    mainContent.className = 'comment-main-content';

    const avatarContainer = document.createElement('div');
    avatarContainer.className = 'comment-avatar';

    const avatar = document.createElement('img');
    avatar.src = comment.avatar_url ? comment.avatar_url : '/img/default-avatar.png';
    avatar.alt = comment.display_name || comment.username;

    avatarContainer.appendChild(avatar);
    mainContent.appendChild(avatarContainer);

    const commentContent = document.createElement('div');
    commentContent.className = 'comment-content';

    const commentHeader = document.createElement('div');
    commentHeader.className = 'comment-header';

    if (comment.parent_comment_id && (comment.parent_username || comment.parent_display_name)) {
        const replyingTo = document.createElement('a');
        replyingTo.className = 'comment-replying-to';
        replyingTo.href = `#comment-${comment.parent_comment_id}`;
        replyingTo.textContent = `@${comment.parent_display_name || comment.parent_username}`;
        replyingTo.addEventListener('click', (e) => {
            e.preventDefault();
            const parentComment = document.querySelector(`#comment-${comment.parent_comment_id}`);
            if (parentComment) {
                parentComment.scrollIntoView({ behavior: 'smooth', block: 'center' });
                parentComment.classList.add('highlight');
                setTimeout(() => {
                    parentComment.classList.remove('highlight');
                }, 2000);
            }
        });

        commentHeader.appendChild(replyingTo);
        commentHeader.appendChild(document.createTextNode(' '));
    }

    const authorName = document.createElement('a');
    authorName.className = 'comment-author';
    authorName.href = `/channel.php?id=${comment.user_id}`;
    authorName.textContent = comment.display_name || comment.username;

    const commentDate = document.createElement('span');
    commentDate.className = 'comment-date';
    commentDate.textContent = formatTimeAgo(comment.created_at);

    commentHeader.appendChild(authorName);
    commentHeader.appendChild(commentDate);
    commentContent.appendChild(commentHeader);

    const commentText = document.createElement('div');
    commentText.className = 'comment-text';
    commentText.textContent = comment.content;
    commentContent.appendChild(commentText);

    const commentActions = document.createElement('div');
    commentActions.className = 'comment-actions';

    const likeButton = document.createElement('button');
    likeButton.className = 'comment-like-btn';
    if (comment.is_liked) {
        likeButton.classList.add('liked');
    }

    const likeCount = document.createElement('span');
    likeCount.className = 'comment-like-count';
    likeCount.textContent = comment.likes_count || '0';

    likeButton.appendChild(likeCount);

    likeButton.addEventListener('click', () => {
        toggleCommentLike(comment.comment_id, likeButton);
    });

    commentActions.appendChild(likeButton);

    if (isLoggedIn) {
        const replyButton = document.createElement('button');
        replyButton.className = 'comment-reply-btn';
        replyButton.textContent = 'Ответить';
        replyButton.addEventListener('click', () => {
            showReplyForm(comment.comment_id, commentItem);
        });

        commentActions.appendChild(replyButton);
    }

    if (!comment.parent_comment_id && comment.replies_count > 0) {
        const repliesButton = document.createElement('button');
        repliesButton.className = 'comment-replies-btn';
        const repliesCount = comment.total_replies_count !== undefined ? comment.total_replies_count : comment.replies_count;
        repliesButton.textContent = `Ответы (${repliesCount})`;
        repliesButton.dataset.loaded = 'false';
        repliesButton.addEventListener('click', () => {
            toggleReplies(comment.comment_id, repliesButton);
        });

        commentActions.appendChild(repliesButton);
    }

    if (comment.is_author) {
        const editButton = document.createElement('button');
        editButton.className = 'comment-edit-btn';
        editButton.textContent = 'Редактировать';
        editButton.addEventListener('click', () => {
            editComment(comment.comment_id, commentItem, comment.content);
        });

        const deleteButton = document.createElement('button');
        deleteButton.className = 'comment-delete-btn';
        deleteButton.textContent = 'Удалить';

        deleteButton.addEventListener('click', () => {
            deleteComment(comment.comment_id, commentItem);
        });

        if (comment.replies_count === 0) {
            commentActions.appendChild(editButton);
            commentActions.appendChild(deleteButton);
        } else {
            editButton.style.display = 'none';
            deleteButton.style.display = 'none';
            commentActions.appendChild(editButton);
            commentActions.appendChild(deleteButton);
        }
    }

    commentContent.appendChild(commentActions);
    mainContent.appendChild(commentContent);
    commentItem.appendChild(mainContent);

    if (!comment.parent_comment_id) {
        const repliesContainer = document.createElement('div');
        repliesContainer.className = 'comment-replies-container';
        commentItem.appendChild(repliesContainer);
    }

    return commentItem;
}

function addComment(videoId, content, parentCommentId = null) {
    return fetch('/api/comments.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            video_id: videoId,
            content: content,
            parent_comment_id: parentCommentId
        })
    });
}

function deleteComment(commentId, commentElement) {
    if (!confirm('Вы уверены, что хотите удалить этот комментарий?')) {
        return;
    }

    fetch(`/api/comments.php?comment_id=${commentId}`, {
        method: 'DELETE',
        headers: {
            'Content-Type': 'application/json'
        }
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                const isReply = commentElement.classList.contains('comment-reply');

                if (isReply) {
                    const repliesContainer = commentElement.closest('.comment-replies-container');
                    const parentCommentElement = repliesContainer.closest('.comment-item');

                    commentElement.remove();

                    const remainingReplies = repliesContainer.querySelectorAll('.comment-reply').length;
                    const repliesButton = parentCommentElement.querySelector('.comment-replies-btn');

                    if (remainingReplies > 0) {
                        if (repliesButton) {
                            repliesButton.textContent = `Ответы (${remainingReplies})`;
                        }

                        if (commentElement.dataset.parentId) {
                            const parentReplyId = commentElement.dataset.parentId;
                            const parentReply = document.querySelector(`#comment-${parentReplyId}`);

                            if (parentReply && parentReply.classList.contains('comment-reply')) {
                                // Проверяем, есть ли у этого родительского ответа другие ответы
                                const hasOtherReplies = Array.from(repliesContainer.querySelectorAll('.comment-reply'))
                                    .some(reply => reply.dataset.parentId === parentReplyId);

                                if (!hasOtherReplies) {
                                    const editButton = parentReply.querySelector('.comment-edit-btn');
                                    const deleteButton = parentReply.querySelector('.comment-delete-btn');

                                    if (editButton) {
                                        editButton.style.display = 'inline-block';
                                    }
                                    if (deleteButton) {
                                        deleteButton.style.display = 'inline-block';
                                    }
                                }
                            }
                        }
                    } else {
                        if (repliesButton) {
                            repliesButton.remove();
                        }

                        repliesContainer.style.display = 'none';
                        const editButton = parentCommentElement.querySelector('.comment-edit-btn');
                        const deleteButton = parentCommentElement.querySelector('.comment-delete-btn');
                        if (editButton) {
                            editButton.style.display = 'inline-block';
                        }
                        if (deleteButton) {
                            deleteButton.style.display = 'inline-block';
                        }
                    }
                } else {
                    commentElement.remove();
                }

                updateCommentsCount(true);

                UIUtils.showNotification('Комментарий успешно удален', 'success');
            } else {
                UIUtils.showNotification(`Ошибка: ${data.message}`, 'error');
            }
        })
        .catch(error => {
            UIUtils.logError('удалении комментария', error);
            UIUtils.showNotification('Произошла ошибка при удалении комментария', 'error');
        });
}

function editComment(commentId, commentElement, currentContent) {
    const editForm = document.createElement('form');
    editForm.className = 'comment-edit-form';
    const textarea = document.createElement('textarea');
    textarea.className = 'comment-edit-textarea';
    textarea.value = currentContent;
    textarea.required = true;

    const buttonsContainer = document.createElement('div');
    buttonsContainer.className = 'comment-edit-buttons';

    const saveButton = document.createElement('button');
    saveButton.type = 'submit';
    saveButton.className = 'comment-edit-save';
    saveButton.textContent = 'Сохранить';

    const cancelButton = document.createElement('button');
    cancelButton.type = 'button';
    cancelButton.className = 'comment-edit-cancel';
    cancelButton.textContent = 'Отмена';
    cancelButton.addEventListener('click', () => {
        commentTextElement.innerHTML = originalContent;
    });

    buttonsContainer.appendChild(saveButton);
    buttonsContainer.appendChild(cancelButton);
    editForm.appendChild(textarea);
    editForm.appendChild(buttonsContainer);

    const commentTextElement = commentElement.querySelector('.comment-text');
    const originalContent = commentTextElement.innerHTML;

    commentTextElement.innerHTML = '';
    commentTextElement.appendChild(editForm);

    textarea.focus();

    editForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const newContent = textarea.value.trim();

        if (!newContent) {
            UIUtils.showNotification('Комментарий не может быть пустым', 'error');
            return;
        }

        fetch('/api/comments.php', {
            method: 'POST',
            headers: {
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({
                action: 'edit',
                comment_id: commentId,
                content: newContent
            })
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    commentTextElement.textContent = newContent;
                    commentElement.dataset.content = newContent;
                    const editButton = commentElement.querySelector('.comment-edit-btn');
                    if (editButton) {
                        const newEditButton = editButton.cloneNode(true);
                        editButton.parentNode.replaceChild(newEditButton, editButton);

                        newEditButton.addEventListener('click', () => {
                            editComment(commentId, commentElement, newContent);
                        });
                    }

                    UIUtils.showNotification('Комментарий успешно отредактирован', 'success');
                } else {
                    UIUtils.showNotification(`Ошибка: ${data.message}`, 'error');
                    commentTextElement.innerHTML = originalContent;
                }
            })
            .catch(error => {
                UIUtils.logError('обновлении комментария', error);
                UIUtils.showNotification('Произошла ошибка при обновлении комментария', 'error');
                commentTextElement.innerHTML = originalContent;
            });
    });
}

function toggleCommentLike(commentId, likeButton) {
    const isLiked = likeButton.classList.contains('liked');
    const action = isLiked ? 'unlike' : 'like';

    fetch('/api/comments.php', {
        method: 'POST',
        headers: {
            'Content-Type': 'application/json'
        },
        body: JSON.stringify({
            action: action,
            comment_id: commentId
        })
    })
        .then(response => response.json())
        .then(data => {
            if (data.success) {
                if (data.is_liked) {
                    likeButton.classList.add('liked');
                } else {
                    likeButton.classList.remove('liked');
                }

                const likesCountElement = likeButton.querySelector('.comment-like-count');
                likesCountElement.textContent = data.likes_count > 0 ? data.likes_count : '0';
            } else {
                UIUtils.showNotification(`Ошибка: ${data.message}`, 'error');
            }
        })
        .catch(error => {
            UIUtils.logError('обработке лайка', error);
        });
}

function updateCommentsCount(isDelete = false) {
    const commentsTitle = document.querySelector('.comments-section h3');
    if (!commentsTitle) return;

    const match = commentsTitle.textContent.match(/(\d+)/);

    if (match) {
        const currentCount = parseInt(match[1], 10);
        const newCount = isDelete ? Math.max(0, currentCount - 1) : currentCount + 1;

        const pluralForm = UIUtils.pluralize(newCount, 'комментарий', 'комментария', 'комментариев');

        commentsTitle.textContent = `${newCount} ${pluralForm}`;

        if (newCount === 0 && isDelete) {
            const commentsContainer = document.getElementById('comments-container');
            commentsContainer.innerHTML = '<div class="no-comments">Комментариев пока нет. Будьте первым!</div>';
        }
    }
}

function formatTimeAgo(dateStr) {
    return UIUtils.formatTimeAgo(dateStr);
}

function showReplyForm(parentCommentId, commentElement) {
    const parentUsername = commentElement.querySelector('.comment-author').textContent;
    const existingForm = commentElement.querySelector('.reply-form');
    if (existingForm) {
        existingForm.remove();
        return;
    }

    const replyForm = document.createElement('form');
    replyForm.className = 'reply-form';

    const textarea = document.createElement('textarea');
    textarea.className = 'reply-input';
    textarea.placeholder = `Ответить пользователю ${parentUsername}...`;
    textarea.required = true;

    const formActions = document.createElement('div');
    formActions.className = 'reply-form-actions';

    const cancelButton = document.createElement('button');
    cancelButton.type = 'button';
    cancelButton.className = 'reply-cancel-btn';
    cancelButton.textContent = 'Отмена';
    cancelButton.addEventListener('click', () => {
        replyForm.remove();
    });

    const submitButton = document.createElement('button');
    submitButton.type = 'submit';
    submitButton.className = 'reply-submit-btn';
    submitButton.textContent = 'Отправить';

    formActions.appendChild(cancelButton);
    formActions.appendChild(submitButton);

    replyForm.appendChild(textarea);
    replyForm.appendChild(formActions);

    replyForm.addEventListener('submit', async (e) => {
        e.preventDefault();

        const content = textarea.value.trim();
        if (!content) return;

        const videoElement = document.querySelector('video');
        const videoId = videoElement.dataset.videoId;

        try {
            const response = await addComment(videoId, content, parentCommentId);
            const reply = await response.json();

            if (reply.success && reply.comment) {
                reply.comment.parent_username = parentUsername;
                reply.comment.is_author = 1;

                const replyElement = createCommentElement(reply.comment);
                let repliesContainer;

                if (commentElement.dataset.parentId) {
                    // Это ответ на ответ, ищем контейнер родительского комментария
                    const parentCommentId = commentElement.dataset.parentId;
                    const parentComment = document.querySelector(`#comment-${parentCommentId}`);
                    if (parentComment) {
                        repliesContainer = parentComment.querySelector('.comment-replies-container');
                    }
                }

                // Если не нашли контейнер родительского комментария или это ответ на основной комментарий
                if (!repliesContainer) {
                    repliesContainer = commentElement.querySelector('.comment-replies-container');
                    if (!repliesContainer) {
                        repliesContainer = document.createElement('div');
                        repliesContainer.className = 'comment-replies-container';
                        repliesContainer.style.display = 'block';
                        commentElement.appendChild(repliesContainer);
                    }
                }

                if (repliesContainer.firstChild) {
                    repliesContainer.insertBefore(replyElement, repliesContainer.firstChild);
                } else {
                    repliesContainer.appendChild(replyElement);
                }

                // Обновляем счетчик ответов
                if (!commentElement.dataset.parentId) {
                    const repliesButton = commentElement.querySelector('.comment-replies-btn');
                    if (repliesButton) {
                        const currentCount = parseInt(repliesButton.textContent.match(/\d+/)[0]);
                        repliesButton.textContent = `Ответы (${currentCount + 1})`;
                    } else {
                        const newRepliesButton = document.createElement('button');
                        newRepliesButton.className = 'comment-replies-btn';
                        newRepliesButton.textContent = 'Ответы (1)';
                        newRepliesButton.dataset.loaded = 'true';
                        newRepliesButton.addEventListener('click', () => {
                            toggleReplies(parentCommentId, newRepliesButton);
                        });

                        const commentActions = commentElement.querySelector('.comment-actions');
                        commentActions.appendChild(newRepliesButton);
                    }
                } else if (commentElement.dataset.parentId) {
                    const parentCommentId = commentElement.dataset.parentId;
                    const parentComment = document.querySelector(`#comment-${parentCommentId}`);
                    if (parentComment) {
                        const repliesButton = parentComment.querySelector('.comment-replies-btn');
                        if (repliesButton) {
                            const currentCount = parseInt(repliesButton.textContent.match(/\d+/)[0]);
                            repliesButton.textContent = `Ответы (${currentCount + 1})`;
                        }
                    }
                }

                replyForm.remove();
                const editButton = commentElement.querySelector('.comment-edit-btn');
                const deleteButton = commentElement.querySelector('.comment-delete-btn');

                if (editButton) {
                    editButton.style.display = 'none';
                }

                if (deleteButton) {
                    deleteButton.style.display = 'none';
                }
            } else {
                UIUtils.showNotification('Произошла ошибка при добавлении ответа', 'error');
            }
        } catch (error) {
            UIUtils.logError('добавлении ответа', error);
            UIUtils.showNotification('Произошла ошибка при добавлении ответа', 'error');
        }
    });

    const commentContent = commentElement.querySelector('.comment-content');
    commentContent.appendChild(replyForm);

    textarea.focus();
}

async function loadReplies(commentId, repliesButton, offset = 0) {
    try {
        const commentElement = repliesButton.closest('.comment-item');
        const repliesContainer = commentElement.querySelector('.comment-replies-container');

        if (offset === 0) {
            repliesContainer.innerHTML = '<div class="loading-replies">Загрузка ответов...</div>';
            repliesContainer.style.display = 'block';
        } else {
            const loadingElement = document.createElement('div');
            loadingElement.className = 'loading-replies';
            loadingElement.textContent = 'Загрузка ответов...';
            repliesContainer.appendChild(loadingElement);
        }

        const response = await fetch(`/api/comments.php?parent_comment_id=${commentId}&offset=${offset}&limit=10&include_nested=true`);
        const result = await response.json();

        const loadingElement = repliesContainer.querySelector('.loading-replies');
        if (loadingElement) {
            loadingElement.remove();
        }

        if (result.success) {
            // Если это первая загрузка и нет ответов, скрываем контейнер
            if (offset === 0 && result.replies.length === 0) {
                repliesContainer.style.display = 'none';
                repliesButton.textContent = 'Ответы (0)';
                return;
            }

            result.replies.forEach(reply => {
                if (reply.user_id == userId && !reply.is_author) {
                    reply.is_author = 1;
                }

                const replyElement = createCommentElement(reply);
                repliesContainer.appendChild(replyElement);
            });

            // Если есть еще ответы для загрузки, добавляем кнопку "Загрузить еще"
            const loadedCount = offset + result.replies.length;
            if (loadedCount < result.total) {
                const loadMoreButton = document.createElement('button');
                loadMoreButton.className = 'load-more-replies';
                loadMoreButton.textContent = `Загрузить еще ответы (${result.total - loadedCount})`;
                loadMoreButton.addEventListener('click', () => {
                    loadMoreButton.remove();
                    loadReplies(commentId, repliesButton, loadedCount);
                });

                repliesContainer.appendChild(loadMoreButton);
            }

            repliesButton.dataset.loaded = 'true';
            repliesButton.textContent = `Ответы (${result.total})`;
        } else {
            UIUtils.showNotification('Ошибка при загрузке ответов', 'error');
            repliesContainer.innerHTML = '<div class="error-loading-replies">Ошибка при загрузке ответов</div>';
        }
    } catch (error) {
        UIUtils.logError('загрузке ответов', error);
        const commentElement = repliesButton.closest('.comment-item');
        const repliesContainer = commentElement.querySelector('.comment-replies-container');
        repliesContainer.innerHTML = '<div class="error-loading-replies">Ошибка при загрузке ответов</div>';
    }
}

function toggleReplies(commentId, repliesButton) {
    const commentElement = repliesButton.closest('.comment-item');
    const repliesContainer = commentElement.querySelector('.comment-replies-container');

    if (repliesButton.dataset.loaded !== 'true') {
        loadReplies(commentId, repliesButton, 0);
        return;
    }

    if (repliesContainer.style.display === 'none' || !repliesContainer.style.display) {
        repliesContainer.style.display = 'block';
    } else {
        repliesContainer.style.display = 'none';
    }
}
