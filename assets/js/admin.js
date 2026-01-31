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
        },

        // Event Types Edit Modal
        initEventTypesModal: function() {
            const modal = $('#sp-edit-type-modal');
            
            // Open modal
            $(document).on('click', '.sp-edit-type', function(e) {
                e.preventDefault();
                const btn = $(this);
                
                $('#edit_type_id').val(btn.data('id'));
                $('#edit_name_ar').val(btn.data('name_ar'));
                $('#edit_name_en').val(btn.data('name_en'));
                $('#edit_icon').val(btn.data('icon'));
                $('#edit_color').val(btn.data('color'));
                $('#edit_attendance_points').val(btn.data('attendance_points'));
                $('#edit_late_points').val(btn.data('late_points') || Math.floor((btn.data('attendance_points') || 0) / 2));
                $('#edit_absence_penalty').val(btn.data('absence_penalty'));
                
                // Excuse points fields
                $('#edit_excuse_points_7plus').val(btn.data('excuse_points_7plus') || 2);
                $('#edit_excuse_points_6').val(btn.data('excuse_points_6') || 3);
                $('#edit_excuse_points_5').val(btn.data('excuse_points_5') || 4);
                $('#edit_excuse_points_4').val(btn.data('excuse_points_4') || 5);
                $('#edit_excuse_points_3').val(btn.data('excuse_points_3') || 6);
                $('#edit_excuse_points_2').val(btn.data('excuse_points_2') || 7);
                $('#edit_excuse_points_1').val(btn.data('excuse_points_1') || 8);
                $('#edit_excuse_points_0').val(btn.data('excuse_points_0') || 10);
                
                modal.show();
            });
            
            // Close modal
            modal.on('click', '.sp-modal-close', function() {
                modal.hide();
            });
            
            $(document).on('keydown', function(e) {
                if (e.key === 'Escape') {
                    modal.hide();
                }
            });
        }
    };

    $(document).ready(function() {
        SPAdmin.init();
        SPAdmin.initEventTypesModal();
    });

})(jQuery);
