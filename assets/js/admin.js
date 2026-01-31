/**
 * Saint Porphyrius - Admin JavaScript
 */

(function($) {
    'use strict';

    const SPAdmin = {
        init: function() {
            this.bindEvents();
        },

        bindEvents: function() {
            // View details modal
            $(document).on('click', '.sp-view-details', function(e) {
                e.preventDefault();
                const link = $(this);
                const modal = $('#sp-pending-modal');

                const fields = {
                    name: link.data('name'),
                    email: link.data('email'),
                    phone: link.data('phone'),
                    home_address: link.data('home-address'),
                    church_name: link.data('church-name'),
                    confession_father: link.data('confession-father'),
                    job_or_college: link.data('job-or-college'),
                    current_church_service: link.data('current-church-service'),
                    church_family: link.data('church-family'),
                    church_family_servant: link.data('church-family-servant'),
                    facebook_link: link.data('facebook-link'),
                    instagram_link: link.data('instagram-link'),
                    created_at: link.data('created-at')
                };

                Object.keys(fields).forEach(function(key) {
                    const value = fields[key] ? fields[key] : '-';
                    const target = modal.find('[data-field="' + key + '"]');

                    if ((key === 'facebook_link' || key === 'instagram_link') && value !== '-') {
                        target.html('<a href="' + value + '" target="_blank">' + value + '</a>');
                    } else {
                        target.text(value);
                    }
                });

                modal.find('.sp-modal-approve').attr('href', link.data('approve-url'));
                modal.find('.sp-modal-reject').attr('href', link.data('reject-url'));

                modal.addClass('is-open').attr('aria-hidden', 'false');
            });

            // Close modal
            $(document).on('click', '[data-close="true"]', function(e) {
                e.preventDefault();
                $('#sp-pending-modal').removeClass('is-open').attr('aria-hidden', 'true');
            });

            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    $('#sp-pending-modal').removeClass('is-open').attr('aria-hidden', 'true');
                }
            });

            // Confirm actions
            $('.sp-reject-btn').on('click', function(e) {
                if (!confirm(spAdmin.strings?.confirmReject || 'Are you sure you want to reject this user?')) {
                    e.preventDefault();
                }
            });
        },

        approveUser: function(pendingId) {
            $.ajax({
                url: spAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sp_approve_user',
                    nonce: spAdmin.nonce,
                    pending_id: pendingId
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error');
                    }
                }
            });
        },

        rejectUser: function(pendingId, reason) {
            $.ajax({
                url: spAdmin.ajaxUrl,
                type: 'POST',
                data: {
                    action: 'sp_reject_user',
                    nonce: spAdmin.nonce,
                    pending_id: pendingId,
                    reason: reason
                },
                success: function(response) {
                    if (response.success) {
                        location.reload();
                    } else {
                        alert(response.data.message || 'Error');
                    }
                }
            });
        }
    };

    $(document).ready(function() {
        SPAdmin.init();
    });

})(jQuery);
