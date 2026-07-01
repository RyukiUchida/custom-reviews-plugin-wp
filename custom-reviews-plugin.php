<?php
/*
Plugin Name: Система публикации отзывов и их вывод
Description: Система создана под тему Twenty Twenty-Five
Version: 1.0
Author: Roman Ryabin
*/

//Регистрация таксономии
function custom_reviews_register_taxonomy() {
    register_taxonomy('reviews_category', 'post', array(
        'label' => __('Reviews'),
        'rewrite' => array('slug' => 'reviews'),
        'hierarchical' => true,
    ));
}
add_action('init', 'custom_reviews_register_taxonomy');

//Создание базовой рубрики при активации плагина (чтобы не вызывать каждый раз БЖ)
register_activation_hook(__FILE__, 'custom_reviews_activate_plugin');
function custom_reviews_activate_plugin() {
    custom_reviews_register_taxonomy(); //Регистрируем перед добавлением
    if (!term_exists('Отзывы', 'reviews_category')) {
        wp_insert_term('Отзывы', 'reviews_category');
    }
    flush_rewrite_rules();
}

//Вывод стилей в head
function custom_reviews_add_styles() {
    if (is_admin()) return;
    ?>
    <style>
        .site-header { position: relative; }
        .custom-review-button-container {
            position: absolute; right: 20px; top: 55px; z-index: 1000;
        }
        .review-button {
            background-color: rgb(0, 0, 0); color: white; padding: 10px 20px;
            border: none; border-radius: 15px; cursor: pointer; margin: 0;
        }
        .review-button:hover { background-color: rgb(58, 58, 58); }
        
        #review-popup {
            display: none; position: fixed; top: 50%; left: 50%;
            transform: translate(-50%, -50%); background: white; padding: 20px;
            border-radius: 10px; box-shadow: 0 0 10px rgba(0, 0, 0, 0.5);
            z-index: 1000; width: 90%; max-width: 500px;
        }
        #review-popup-overlay {
            display: none; position: fixed; top: 0; left: 0;
            width: 100%; height: 100%; background: rgba(0, 0, 0, 0.5); z-index: 999;
        }
        .review-form input, .review-form textarea {
            width: 100%; margin-bottom: 10px; padding: 8px;
            border: 1px solid #ccc; border-radius: 4px; box-sizing: border-box;
        }
        .review-form button {
            background-color: rgb(0, 0, 0); color: white; padding: 10px 20px;
            border: none; border-radius: 5px; cursor: pointer;
        }
        .review-form button:hover { background-color: rgb(58, 58, 58); }
        .error { color: red; font-size: 0.9em; display: block; margin-bottom: 10px; }
        .reviews-section { margin: 20px auto; max-width: 600px; }
        .review-item { border-bottom: 1px solid #eee; padding: 10px 0; }
    </style>
    <?php
}
add_action('wp_head', 'custom_reviews_add_styles');

//Вывод HTML и JS в футере (чтобы не блокировать рендеринг)
function custom_reviews_add_footer_elements() {
    if (is_admin()) return;
    ?>
    <div class="custom-review-button-container">
        <button id="open-review-form" class="review-button">Оставить отзыв</button>
    </div>

    <div id="review-popup-overlay"></div>
    <div id="review-popup">
        <form id="review-form" class="review-form">
            <h2>Оставить отзыв</h2>
            <div>
                <label for="review-name">Имя:</label>
                <input type="text" id="review-name" name="review_name" required>
                <span class="error" id="name-error"></span>
            </div>
            <div>
                <label for="review-email">Email:</label>
                <input type="email" id="review-email" name="review_email" required>
                <span class="error" id="email-error"></span>
            </div>
            <div>
                <label for="review-text">Отзыв:</label>
                <textarea id="review-text" name="review_text" rows="5" required></textarea>
                <span class="error" id="text-error"></span>
            </div>
            <button type="submit" id="submit-review-btn">Отправить</button>
            <button type="button" id="close-review-form">Закрыть</button>
        </form>
    </div>

    <script>
        //Передача данных WordPress в JS-переменную (подготовка для кэширования)
        const customReviewsData = {
            ajaxUrl: '<?php echo esc_url(admin_url('admin-ajax.php')); ?>',
            nonce: '<?php echo wp_create_nonce('review_nonce'); ?>'
        };

        document.addEventListener('DOMContentLoaded', function() {
            const openButton = document.getElementById('open-review-form');
            const popup = document.getElementById('review-popup');
            const overlay = document.getElementById('review-popup-overlay');
            const closeButton = document.getElementById('close-review-form');
            const form = document.getElementById('review-form');
            const submitBtn = document.getElementById('submit-review-btn');

            const togglePopup = (show) => {
                const display = show ? 'block' : 'none';
                if (popup) popup.style.display = display;
                if (overlay) overlay.style.display = display;
            };

            if (openButton) openButton.addEventListener('click', () => togglePopup(true));
            if (closeButton) closeButton.addEventListener('click', () => togglePopup(false));
            if (overlay) overlay.addEventListener('click', () => togglePopup(false));

            if (form) {
                form.addEventListener('submit', function(e) {
                    e.preventDefault();

                    document.querySelectorAll('.error').forEach(el => el.textContent = '');
                    submitBtn.disabled = true; //Защита от двойного клика

                    const name = document.getElementById('review-name').value.trim();
                    const email = document.getElementById('review-email').value.trim();
                    const text = document.getElementById('review-text').value.trim();

                    let isValid = true;

                    if (!name) {
                        document.getElementById('name-error').textContent = 'Имя не может быть пустым';
                        isValid = false;
                    }

                    if (!/^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(email)) {
                        document.getElementById('email-error').textContent = 'Введите корректный email';
                        isValid = false;
                    }

                    if (/<[^>]+>/.test(text)) {
                        document.getElementById('text-error').textContent = 'Отзыв не должен содержать HTML';
                        isValid = false;
                    }

                    if (isValid) {
                        const formData = new URLSearchParams({
                            action: 'submit_review',
                            name: name,
                            email: email,
                            text: text,
                            nonce: customReviewsData.nonce
                        });

                        fetch(customReviewsData.ajaxUrl, {
                            method: 'POST',
                            headers: { 'Content-Type': 'application/x-www-form-urlencoded' },
                            body: formData.toString()
                        })
                        .then(response => response.json())
                        .then(data => {
                            if (data.success) {
                                alert('Отзыв успешно отправлен!');
                                form.reset();
                                togglePopup(false);
                            } else {
                                alert('Ошибка: ' + (data.data?.message || 'Неизвестная ошибка'));
                            }
                        })
                        .catch(error => {
                            console.error('Ошибка AJAX:', error);
                            alert('Ошибка при отправке отзыва. Проверьте консоль для деталей');
                        })
                        .finally(() => {
                            submitBtn.disabled = false;
                        });
                    } else {
                        submitBtn.disabled = false;
                    }
                });
            }
        });
    </script>
    <?php
}
add_action('wp_footer', 'custom_reviews_add_footer_elements');

//Обработка AJAX
function custom_reviews_submit_review() {
    if (!check_ajax_referer('review_nonce', 'nonce', false)) {
        wp_send_json_error(['message' => 'Ошибка безопасности. Попробуйте обновить страницу.']);
    }

    $name = sanitize_text_field($_POST['name'] ?? '');
    $email = sanitize_email($_POST['email'] ?? '');
    $text = sanitize_textarea_field($_POST['text'] ?? '');

    if (empty($name)) wp_send_json_error(['message' => 'Имя не может быть пустым']);
    if (!is_email($email)) wp_send_json_error(['message' => 'Некорректный email']);
    if (preg_match('/<[^>]+>/', $text)) wp_send_json_error(['message' => 'Отзыв не должен содержать HTML']);

    $post_id = wp_insert_post(array(
        'post_title'   => 'Отзыв от ' . $name,
        'post_content' => $text,
        'post_status'  => 'publish',
        'post_type'    => 'post',
        'meta_input'   => array(
            'review_email' => $email,
            'review_name'  => $name
        )
    ));

    if (is_wp_error($post_id)) {
        wp_send_json_error(['message' => 'Ошибка сохранения: ' . $post_id->get_error_message()]);
    }

    $term = get_term_by('name', 'Отзывы', 'reviews_category');
    if ($term) {
        wp_set_post_terms($post_id, array($term->term_id), 'reviews_category');
    }

    wp_send_json_success();
}
add_action('wp_ajax_submit_review', 'custom_reviews_submit_review');
add_action('wp_ajax_nopriv_submit_review', 'custom_reviews_submit_review');

//Шорткод
function custom_reviews_shortcode() {
    ob_start();

    $args = array(
        'post_type'      => 'post',
        'posts_per_page' => 3,
        'post_status'    => 'publish',
        'orderby'        => 'date',
        'order'          => 'DESC',
        'tax_query'      => array(
            array(
                'taxonomy' => 'reviews_category',
                'field'    => 'name',
                'terms'    => 'Отзывы',
            ),
        ),
    );

    $reviews = new WP_Query($args);

    if ($reviews->have_posts()) {
        echo '<div class="reviews-section"><h2>Последние отзывы</h2>';
        while ($reviews->have_posts()) {
            $reviews->the_post();
            $name = get_post_meta(get_the_ID(), 'review_name', true);
            ?>
            <div class="review-item">
                <h3><?php echo esc_html($name); ?></h3>
                <p><?php echo wp_kses_post(get_the_content()); ?></p>
                <small><?php echo esc_html(get_the_date()); ?></small>
            </div>
            <?php
        }
        echo '</div>';
    } else {
        echo '<p>Нет отзывов для отображения</p>';
    }
    wp_reset_postdata();

    return ob_get_clean();
}
add_shortcode('recent_reviews', 'custom_reviews_shortcode');
?>