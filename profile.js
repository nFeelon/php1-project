document.addEventListener('DOMContentLoaded', function () {
    initEventHandlers();
});

function initEventHandlers() {
    const navItems = document.querySelectorAll('.profile-nav-item');
    const sections = document.querySelectorAll('.profile-section');

    navItems.forEach(item => {
        item.addEventListener('click', function (e) {
            e.preventDefault();

            navItems.forEach(navItem => navItem.classList.remove('active'));
            this.classList.add('active');
            const targetId = this.getAttribute('href').substring(1);
            sections.forEach(section => section.style.display = 'none');
            document.getElementById(targetId).style.display = 'block';
        });
    });

    const profileForm = document.getElementById('profile-form');
    if (profileForm) {
        profileForm.addEventListener('submit', function (e) {
            e.preventDefault();

            const formData = new FormData(this);
            const displayName = formData.get('display_name');
            const description = formData.get('description');
            const vk = formData.get('vk');
            const instagram = formData.get('instagram');
            const facebook = formData.get('facebook');

            const data = {
                display_name: displayName,
                description: description,
                social_links: {
                    vk: vk,
                    instagram: instagram,
                    facebook: facebook
                }
            };

            fetch('/api/update_profile.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify(data)
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        UIUtils.showNotification(data.message || 'Профиль успешно обновлен', 'success');

                        const displayNameElement = document.getElementById('display-name');
                        if (displayNameElement) {
                            displayNameElement.textContent = data.user.display_name;
                        }

                        updateSocialLinks(data.user.social_links);
                    } else {
                        UIUtils.showNotification(data.message || 'Ошибка при обновлении профиля', 'error');
                    }
                })
                .catch(error => {
                    UIUtils.logError('обновлении профиля', error);
                    UIUtils.showNotification('Произошла ошибка при обновлении профиля', 'error');
                });
        });
    }
    
    const avatarInput = document.getElementById('avatar-input');
    if (avatarInput) {
        avatarInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const previewImg = document.getElementById('avatar-preview');
                if (previewImg) {
                    previewImg.src = URL.createObjectURL(this.files[0]);
                }
                uploadMedia(this.files[0], 'avatar');
            }
        });
    }

    const bannerInput = document.getElementById('banner-input');
    if (bannerInput) {
        bannerInput.addEventListener('change', function () {
            if (this.files && this.files[0]) {
                const previewImg = document.getElementById('banner-preview');
                if (previewImg) {
                    previewImg.src = URL.createObjectURL(this.files[0]);
                } else {
                    const bannerPreview = document.querySelector('.banner-preview');
                    if (bannerPreview) {
                        bannerPreview.innerHTML = '';
                        const img = document.createElement('img');
                        img.id = 'banner-preview';
                        img.src = URL.createObjectURL(this.files[0]);
                        img.alt = 'Баннер';
                        bannerPreview.appendChild(img);
                    }
                }
                uploadMedia(this.files[0], 'banner');
            }
        });
    }

    function uploadMedia(file, mediaType) {
        if (!file) return;

        const formData = new FormData();
        formData.append('file', file);
        formData.append('media_type', mediaType);

        fetch('/api/update_profile_media.php', {
            method: 'POST',
            body: formData
        })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    UIUtils.showNotification(data.message || `${mediaType === 'avatar' ? 'Аватар' : 'Баннер'} успешно обновлен`, 'success');

                    if (mediaType === 'avatar') {
                        const avatarImg = document.getElementById('profile-avatar');
                        if (avatarImg) {
                            avatarImg.src = data.url;
                        }
                    } else if (mediaType === 'banner') {
                        const banner = document.querySelector('.profile-banner');
                        if (banner) {
                            if (banner.querySelector('img')) {
                                banner.querySelector('img').src = data.url;
                            } else {
                                const img = document.createElement('img');
                                img.src = data.url;
                                img.alt = 'Баннер профиля';
                                banner.appendChild(img);
                                banner.classList.remove('profile-banner-default');
                            }
                        }
                    }
                } else {
                    UIUtils.showNotification(data.message || `Ошибка при загрузке ${mediaType === 'avatar' ? 'аватара' : 'баннера'}`, 'error');
                }
            })
            .catch(error => {
                UIUtils.logError(`загрузке ${mediaType === 'avatar' ? 'аватара' : 'баннера'}`, error);
                UIUtils.showNotification(`Произошла ошибка при загрузке ${mediaType === 'avatar' ? 'аватара' : 'баннера'}`, 'error');
            });
    }

    document.querySelectorAll('.status-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            const videoId = this.querySelector('input[name="video_id"]').value;
            const status = this.querySelector('select[name="status"]').value;

            fetch('/api/manage_video.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'update_status',
                    video_id: videoId,
                    status: status
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        UIUtils.showNotification(data.message || 'Статус видео успешно изменен', 'success');

                        const videoRow = document.querySelector(`.video-row[data-video-id="${videoId}"]`);
                        if (videoRow) {
                            const statusCell = videoRow.querySelector('.video-status');
                            if (statusCell) {
                                statusCell.className = `video-status ${status}`;
                                let statusText = '';
                                switch (status) {
                                    case 'public':
                                        statusText = 'Публичное';
                                        break;
                                    case 'private':
                                        statusText = 'Приватное';
                                        break;
                                    case 'unlisted':
                                        statusText = 'По ссылке';
                                        break;
                                    default:
                                        statusText = status;
                                }
                                statusCell.textContent = statusText;
                            }
                        }
                    } else {
                        UIUtils.showNotification(data.message || 'Ошибка при изменении статуса видео', 'error');
                    }
                })
                .catch(error => {
                    UIUtils.logError('изменении статуса видео', error);
                    UIUtils.showNotification('Произошла ошибка при изменении статуса видео', 'error');
                });
        });
    });

    document.querySelectorAll('.delete-video-form').forEach(form => {
        form.addEventListener('submit', function (e) {
            e.preventDefault();

            if (!confirm('Вы уверены, что хотите удалить это видео?')) {
                return;
            }

            const videoId = this.querySelector('input[name="video_id"]').value;

            fetch('/api/manage_video.php', {
                method: 'POST',
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    action: 'delete',
                    video_id: videoId
                })
            })
                .then(response => response.json())
                .then(data => {
                    if (data.success) {
                        UIUtils.showNotification(data.message || 'Видео успешно удалено', 'success');

                        const videoRow = document.querySelector(`.video-row[data-video-id="${videoId}"]`);
                        if (videoRow) {
                            videoRow.remove();
                        }

                        const videosTable = document.querySelector('.videos-table');
                        if (videosTable && videosTable.querySelectorAll('.video-row').length === 0) {
                            const tableContainer = document.querySelector('.videos-table-container');
                            if (tableContainer) {
                                const noVideosMessage = document.createElement('p');
                                noVideosMessage.className = 'no-videos';
                                noVideosMessage.textContent = 'У вас пока нет видео';
                                tableContainer.innerHTML = '';
                                tableContainer.appendChild(noVideosMessage);
                            }
                        }
                    } else {
                        UIUtils.showNotification(data.message || 'Ошибка при удалении видео', 'error');
                    }
                })
                .catch(error => {
                    UIUtils.logError('удалении видео', error);
                    UIUtils.showNotification('Произошла ошибка при удалении видео', 'error');
                });
        });
    });

    function updateSocialLinks(socialLinks) {
        const socialContainer = document.querySelector('.social-links');
        if (!socialContainer) return;

        socialContainer.innerHTML = '';
        if (socialLinks && socialLinks.vk) {
            const vkLink = document.createElement('a');
            vkLink.href = socialLinks.vk;
            vkLink.className = 'social-link vk';
            vkLink.target = '_blank';
            vkLink.innerHTML = '<i class="fab fa-vk"></i>';
            socialContainer.appendChild(vkLink);
        }

        if (socialLinks && socialLinks.instagram) {
            const instagramLink = document.createElement('a');
            instagramLink.href = socialLinks.instagram;
            instagramLink.className = 'social-link instagram';
            instagramLink.target = '_blank';
            instagramLink.innerHTML = '<i class="fab fa-instagram"></i>';
            socialContainer.appendChild(instagramLink);
        }

        if (socialLinks && socialLinks.facebook) {
            const facebookLink = document.createElement('a');
            facebookLink.href = socialLinks.facebook;
            facebookLink.className = 'social-link facebook';
            facebookLink.target = '_blank';
            facebookLink.innerHTML = '<i class="fab fa-facebook"></i>';
            socialContainer.appendChild(facebookLink);
        }
    }
}
