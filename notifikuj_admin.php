<?php
class Notifikujcz
{
    private $notifikuj_cz_options;

    public function __construct()
    {
        add_action('admin_menu', array($this, 'notifikuj_cz_add_plugin_page'));
        add_action('admin_init', array($this, 'notifikuj_cz_page_init'));
    }

    public function notifikuj_cz_add_plugin_page()
    {
        add_menu_page(
            'Notifikuj.cz', // page_title
            'Notifikuj.cz', // menu_title
            'manage_options', // capability
            'notifikuj-cz', // menu_slug
            array($this, 'notifikuj_cz_create_admin_page'), // function
            plugin_dir_url(__FILE__) . 'nf_logo_ikona.png', // icon_url
            59 // position
        );
    }

    public function notifikuj_cz_create_admin_page()
    {
        $this->notifikuj_cz_options = get_option('notifikuj_cz_option_name'); ?>
        <style>
            .notifikuj-logo {
                width: 250px;
                height: auto;
            }

            input,
            .notifikuj-btn {
                padding: 0.375rem 0.75rem !important;
                color: #495057 !important;
                background-color: #fff !important;
                background-clip: padding-box !important;
                border: 1px solid #ced4da !important;
                border-radius: 0.5rem !important;
                transition: border-color .15s ease-in-out, box-shadow .15s ease-in-out !important;
            }

            .notifikuj-btn {
                line-height: 2.15384615;
                cursor: pointer;
                margin-top: 1rem;
                margin-bottom: 1rem;
            }

            .form-table td {
                padding: 15px 0px;
            }

            .no-padding {
                padding: 5px 0px !important;
            }

            .notice-success,
            div.updated {
                border-left-color: #7DC700 !important;
            }

            a {
                color: #1d2327;
            }

            a:hover {
                color: #7DC700;
            }
        </style>

        <div class="wrap">
            <img class="notifikuj-logo" src="<?php echo esc_url(plugins_url('/logo.png', __FILE__)); ?>" alt="">
            <p>S Notifikuj zobrazíte na webu oznámení o aktivitě zákazníků. Tím dáte návštěvníkům jednoduchý a jasný důkaz, že se u vás stále a s oblibou nakupuje.</p>
            <?php settings_errors(); ?>

            <h2>Registrace nového účtu</h2>

            <?php
            if (isset($_POST['SubmitButton'])) {
                $password = sanitize_text_field($_POST['password']);
                if (empty($_POST['advertisingConsent'])) {
                    $advertisingConsent = true;
                } else {
                    $advertisingConsent = false;
                }
                $surname = sanitize_text_field($_POST['surname']);
                $firstname = sanitize_text_field($_POST['name']);
                $email = sanitize_email($_POST['email']);
                $passwordAgain = sanitize_text_field($_POST['password-2']);

                $data = array(
                    "password" => $password,
                    "advertisingConsent" => $advertisingConsent,
                    "surname" => $surname,
                    "firstname" => $firstname,
                    "email" => $email,
                    "passwordAgain" => $passwordAgain
                );

                $data_json = json_encode($data);

                $response = wp_remote_post(
                    'https://appi.notifikuj.cz:444/create-account',
                    array(
                        'headers' => array(
                            'Content-Type' => 'application/json'
                        ),
                        'body' => $data_json
                    )
                );

                $api_response = wp_remote_retrieve_body($response);
                $response_rendered = json_decode($api_response, true);

                if ($response_rendered["code"] === 'registration-success') {
                    echo wp_kses_post('<div class="notice notice-success is-dismissible">
                  <p>Úspěšná registrace</p>
                  </div>');

                    echo wp_kses_post('<div class="notice notice-success is-dismissible">
                  <p>UUID: ' . $response_rendered["uuid"] . '</p>
                  </div>');

                    $this->notifikuj_cz_options['uuid_0'] = $response_rendered["uuid"];
                    update_option('notifikuj_cz_option_name', $this->notifikuj_cz_options);
                } elseif ($response_rendered["code"] === 'existing-email-error') {
                    echo wp_kses_post('<div class="notice notice-warning is-dismissible">
                <p>Tato e-mailová adresa se již používá</p>
                </div>');
                } else {
                    echo wp_kses_post('<div class="notice notice-warning is-dismissible">
                <p>' . $response_rendered["message"] . '</p>
                </div>');
                }
            }


            ?>
            <div id="register-errors">

            </div>
            <form method="post">
                <table id="register" class="form-table" role="presentation">
                    <tbody>
                        <tr>
                            <td>
                                <input class="regular-text" type="text" name="name" id="name" placeholder="Jméno" required>
                                <input class="regular-text" type="text" name="surname" id="surname" placeholder="Příjmení" required>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input class="regular-text" type="email" name="email" id="email" placeholder="Email" required>
                            </td>
                        </tr>
                        <tr>
                            <td>
                                <input class="regular-text" type="password" name="password" id="password" placeholder="Heslo" required minlength="6">
                                <input class="regular-text" type="password" name="password-2" id="password-2" placeholder="Heslo znovu" required minlength="6">
                            </td>
                        </tr>
                        <tr>
                            <td class="no-padding">
                                <label>
                                    <input name="termsConsent" type="checkbox" class="termsConsent" required>
                                    <span>
                                        Souhlasím s <a target="_blank" href="https://notifikuj.cz/vseobecne-obchodni-podminky/">podmínkami služby</a>.
                                    </span>
                                </label>
                            </td>
                        </tr>
                        <tr>
                            <td class="no-padding">
                                <label>
                                    <input name="advertisingConsent" type="checkbox" class="advertisingConsent">
                                    <span>
                                        Mám zájem o speciální akce a slevy i když nesouvisejí přímo se systémem Notifikuj.
                                        <a target="_blank" href="https://notifikuj.cz/vseobecne-obchodni-podminky/">Více informací</a>.
                                    </span>
                                </label>
                            </td>
                        </tr>
                    </tbody>
                </table>

                <input type="submit" id="notifikuj-btn" class="notifikuj-btn" value="Vytvořit nový účet a automaticky napojit" name="SubmitButton">
            </form>

            <script>
                document.getElementById("notifikuj-btn").addEventListener("click", function(event) {
                    var password = document.getElementById("password").value;
                    var password2 = document.getElementById("password-2").value;
                    if (password === password2) {} else {
                        event.preventDefault();
                        // password and password-2 do not match, show an error message
                        document.getElementById('register-errors').innerHTML = '<div id="register-errors" class="notice notice-warning is-dismissible"><p>Špatně zadané heslo nebo hesla nejsou stejná</p></div>';
                    }
                });
            </script>

            <form method="post" action="options.php">
                <?php
                settings_fields('notifikuj_cz_option_group');
                do_settings_sections('notifikuj-cz-admin');
                submit_button();
                ?>
            </form>
        </div>
<?php }

    public function notifikuj_cz_page_init()
    {
        register_setting(
            'notifikuj_cz_option_group', // option_group
            'notifikuj_cz_option_name', // option_name
            array($this, 'notifikuj_cz_sanitize') // sanitize_callback
        );

        add_settings_section(
            'notifikuj_cz_setting_section', // id
            'Máte již účet založený? Prihláste sa <a target="_blank" href="https://app.notifikuj.cz/Login">zde</a>. Zkopírujte vaše UUID číslo a zadejte ho sem. Pokud Vaše UUID číslo neznáte, kontaktujte naši podporu na <a href="mailto:info@notifikuj.cz">info@notifikuj.cz</a>', // title
            array($this, 'notifikuj_cz_section_info'), // callback
            'notifikuj-cz-admin' // page
        );

        add_settings_field(
            'uuid_0', // id
            'UUID', // title
            array($this, 'uuid_0_callback'), // callback
            'notifikuj-cz-admin', // page
            'notifikuj_cz_setting_section' // section
        );
    }

    public function notifikuj_cz_sanitize($input)
    {
        $sanitary_values = array();
        if (isset($input['uuid_0'])) {
            $sanitary_values['uuid_0'] = sanitize_text_field($input['uuid_0']);
        }

        return $sanitary_values;
    }

    public function notifikuj_cz_section_info()
    {
    }

    public function uuid_0_callback()
    {
        printf(
            '<input class="regular-text" type="text" name="notifikuj_cz_option_name[uuid_0]" id="uuid_0" value="%s">',
            isset($this->notifikuj_cz_options['uuid_0']) ? esc_attr($this->notifikuj_cz_options['uuid_0']) : ''
        );
    }
}
if (is_admin())
    $notifikuj_cz = new Notifikujcz();

/* 
 * Retrieve this value with:
 * $notifikuj_cz_options = get_option( 'notifikuj_cz_option_name' ); // Array of All Options
 * $uuid_0 = $notifikuj_cz_options['uuid_0']; // UUID
 */

$notifikuj_cz_options = get_option('notifikuj_cz_option_name'); // Array of All Options
if (empty($notifikuj_cz_options)) {
    return;
} else {
    $uuid_0 = $notifikuj_cz_options['uuid_0']; // UUID
}
if (!empty($uuid_0)) {

    function enqueue_notifikuj_script()
    {
        global $uuid_0;
        wp_enqueue_script('notifikuj-script', '//app.notifikuj.cz/js/notifikuj.min.js?id=' . $uuid_0, array(), null, true);
    }
    add_action('wp_enqueue_scripts', 'enqueue_notifikuj_script');
}
