<?php
#############################################################################
#                                                                           #
#       This webpage is a gui for a custom craftcms installation            #
#   it is supposed to be used alongside a docker compose and docker file    #
#        the scripts and docker files were created by Luke Kahms            #
#           DO NOT USE THIS HELPER IN PRODUCTION - DEV ONLY                 #
#                                                                           #
#############################################################################



/* how this file works
    Depending on the $_GET this page displays different subpages to guide the user through the installation
    this php file also acts as a bridge between the craft systems and the webpage and executes commands
    the commands to be executed are requested via the POST Method and called by itself via js (it acts as its own API)
    In the first section the variables are defined, after that all of the craft interactions are defined and in the third section below the web pages are generated
    why all of this in one file? That way it is compact and easy on the user side - sorry :)
*/

//-------SECTION-----DEPENDENCIES-----
if (file_exists('vendor/autoload.php')) {
    require 'vendor/autoload.php'; // require composer dependencies
}


use Composer\Console\Application;
use Symfony\Component\Console\Input\ArrayInput;

//-------SECTION-----Variables------Method:GET----------
if (isset($_GET["page"])) {
    $page = $_GET["page"];
}else{
    $page = "welcome";
}



//-------SECTION-----craft-interactions------Method:POST----------
if ($_SERVER['REQUEST_METHOD'] == "POST") {
    $data = json_decode(file_get_contents('php://input'), true);


    //----------SUB-SECTION--------checks-------


    //this checks for the initial installation of composer in html --> needed to build craft project
    if (isset($data ["checkinstalled"])) {
        if(file_exists("/var/www/html/installed")){
            echo(json_encode(true));
        }else {
            echo(json_encode(false));
        }
    }

    //this checks if craft has been setup
    if (isset($data ["checksetup"])) {
        if(file_exists("checksetup")){
            echo(json_encode(true));
        }else{
            echo(json_encode(false));
        }
    }

    //this checks if the project name is unique
    if (isset($data ["uniqueproject"])) {
        $projectname = $data ["uniqueproject"];
        if(! file_exists("/var/www/html/$projectname")){
            echo(json_encode(true));
        }else {
            echo(json_encode(false));
        }
    }


    //----------SUB-SECTION--------actions-------

    //This creates the craft project and dependecies and updates the db infos
    if (isset($data ["buildcraft"])) {
        //these post values must be set
        $projectname = $data ["projectname"];
        $CRAFT_DB_DRIVER = $data ["dbdriver"];
        $CRAFT_DB_SERVER = $data ["dbserver"];
        $CRAFT_DB_PORT = $data ["dbport"];
        $CRAFT_DB_DATABASE = $data ["dbdatabase"];
        $CRAFT_DB_USER = $data ["dbuser"];
        $CRAFT_DB_PASSWORD = $data ["dbpassword"];
        $CRAFT_DB_SCHEMA = $data ["dbschema"];
        $CRAFT_DB_TABLE_PREFIX = $data ["dbtableprefix"];
        $craftproject = "/var/www/html/$projectname";
        $env_filepath = "$craftproject/.env";

        //credit: https://stackoverflow.com/a/25208897
        // Composer\Factory::getHomeDir() method 
        // needs COMPOSER_HOME environment variable set
        putenv('COMPOSER_HOME=' . __DIR__ . '/vendor/bin/composer');

        // call `composer install` command programmatically
        $input = new ArrayInput(array('command' => 'install'));
        $application = new Application();
        $application->setAutoExit(false); // prevent `$application->run` method from exitting the script
        $application->run($input);

        shell_exec("composer create-project craftcms/craft $projectname");


        //updating the Environment .env file in craft-project
        shell_exec("echo %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%");
        shell_exec("echo Updating the database configuration");
        shell_exec("echo -------------------------------------");
        shell_exec("echo reading .env file");
        $env = file_get_contents($env_filepath);
        shell_exec("echo -------------------------------------");
        shell_exec("echo updating values");
        $env = preg_replace('/^(CRAFT_DB_DRIVER=).*/m', '${1}' . $CRAFT_DB_DRIVER, $env);
        $env = preg_replace('/^(CRAFT_DB_SERVER=).*/m', '${1}' . $CRAFT_DB_SERVER, $env);
        $env = preg_replace('/^(CRAFT_DB_PORT=).*/m', '${1}' . $CRAFT_DB_PORT, $env);
        $env = preg_replace('/^(CRAFT_DB_DATABASE=).*/m', '${1}' . $CRAFT_DB_DATABASE, $env);
        $env = preg_replace('/^(CRAFT_DB_USER=).*/m', '${1}' . $CRAFT_DB_USER, $env);
        $env = preg_replace('/^(CRAFT_DB_PASSWORD=).*/m', '${1}' . $CRAFT_DB_PASSWORD, $env);
        $env = preg_replace('/^(CRAFT_DB_SCHEMA=).*/m', '${1}' . $CRAFT_DB_SCHEMA, $env);
        $env = preg_replace('/^(CRAFT_DB_TABLE_PREFIX=).*/m', '${1}' . $CRAFT_DB_TABLE_PREFIX, $env);
        shell_exec("echo -------------------------------------");
        shell_exec("echo Saving configuration");
        file_put_contents("$env_filepath", $env);
        shell_exec("echo %%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%%");

        // delete vendor folder, composer.json and composer.lock
        shell_exec("rm -rf /var/www/html/vendor");
        shell_exec("rm /var/www/html/composer.json");
        shell_exec("rm /var/www/html/composer.lock");


        echo(json_encode(true));
    }


    //this sets up the craft website
    if (isset($data["setupcraft"])) {
        $craftproject = $data["projectname"];
        $mail = $data["mail"];
        $username = $data["username"];
        $password = $data["password"];
        $sitename = $data["pagename"];
        $siteurl = "http://localhost:3380";
        $language = $data["language"];


        $message = exec("cd $craftproject; php craft install/craft --email $mail --username $username --password $password --site-name $sitename --site-url $siteurl --language $language", $output);
        echo(json_encode($output));
    }



    //this restarts the web server and writes the craft project name (given in $data[restrt])to file
    if (isset($data ["restart"])) {
        file_put_contents("/var/www/html/setup",$data ["restart"]);
        shell_exec("sudo /etc/init.d/apache2 restart");
    }

    //this figures out what the craft installation was named and restores it
    if (isset($data ["restore"])) {
        $content = scandir("/var/www/html");
        foreach ($content as $entry) {
            if (is_dir($entry) && $entry != "." && $entry != ".." && $entry != "vendor") {
                $restoreprojectname = $entry;
            }
        }
        file_put_contents("/var/www/html/setup",$restoreprojectname);
        shell_exec("sudo /etc/init.d/apache2 restart");
    }


}




//-------SECTION-----web-pages------Method:NOT>POST----------
// the web pages were created with the help of chatgpt
function welcomepage(){
    echo <<<WELCOMEPAGE
    <style>
    body {
        font-family: Arial, sans-serif;
        background-color: #f5f5f5;
        margin: 0;
        padding: 0;
    }
    .progress-bar {
        background-color: #007BFF;
        height: 8px;
        width: 25%;
    }
    .container {
        max-width: 50em;
        margin: 2em auto;
        padding: 1.25em;
        background-color: #fff;
        box-shadow: 0 0.125em 0.25em rgba(0, 0, 0, 0.1);
        border-radius: 0.5em;
        text-align: center;
    }
    h1 {
        font-size: 1.5em;
        color: #333;
    }
    p {
        font-size: 1em;
        color: #666;
    }
    .credits {
        font-size: 0.75em;
        color: #999;
    }
    .checkbox-label {
        display: block;
        margin: 1.25em 0;
    }
    .checkbox-label input {
        margin-right: 0.625em;
    }
    .button {
        background-color: #007BFF;
        color: #fff;
        border: none;
        padding: 0.625em 1.25em;
        border-radius: 0.5em;
        cursor: pointer;
        font-size: 1.125em;
    }
    .button:hover {
        background-color: #0056b3;
    }
    button[disabled=disabled], button:disabled {
        background-color: #ccc;
        color: #666;
        cursor: not-allowed;
    }

    #restore-button{
        position: absolute;
        top: 90vh;
        right: 50px;
        font-size: 0.9em;
        background-color: lightblue;
        color: #fff;
        border: none;
        padding: 0.625em 1.25em;
        border-radius: 0.5em;
        cursor: pointer;
    }

    #restore-button:hover{
        background-color: #0056b3;
    }


    </style>
    <div class="progress-bar"></div>
    <div class="container">
        <h1>Welcome to Craft CMS Installation Assistant</h1>
        <p>Craft CMS is a powerful and flexible content management system.</p>
        <p>This installation assistant helps you set up Craft CMS for development purposes.</p>
        <p class="credits">This script was made by: Luke Kahms<br>
        The installer is not fully tested. If you encounter any bugs, contact me via <a href="mailto:luke.kahms@student.fh-kiel.de">e-mail</a></p>
        <label class="checkbox-label">
            <input type="checkbox" id="development-checkbox">
            I understand that this assistant is for development use only, not for production.
        </label>
        <button class="button" id="start-button" disabled onclick="checkForInstallation()">Start Installation</button>
    </div>
    <button id="restore-button" onclick="restore()">Restore Project</button>
    <script>
    document.getElementById("development-checkbox").addEventListener("change", function() {
        var startButton = document.getElementById("start-button");
        startButton.disabled = !this.checked;
    })
    function checkForInstallation() {
        showLoadingPopup("Preparing Installation");
        fetchifinstalled();
    }

    function fetchifinstalled(){
        fetch('index.php', {
            method: "POST",
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ "checkinstalled": true })
        })
        .then(response => response.json())
        .then(response => {
                if(response == true){
                    window.location = "index.php?page=projectsetup"
                }else{
                    setTimeout(() => {
                        fetchifinstalled();
                    }, 30000);
                }
            }
        )
        .catch(error => {
            setTimeout(fetchifinstalled, 10000);
        });
    };


    function restore(){
        showLoadingPopup("Restoring project")
        fetch('index.php', {
            method: "POST",
            headers: {
                'Accept': 'application/json',
                'Content-Type': 'application/json'
            },
            body: JSON.stringify({ "restore": true})
        })
        .then(response => response.json())
        .then(response => {
                if(response == true){
                    setTimeout(function() {
                        window.location.href = "/";
                    }, 20000);
                }
            }
        )
        .catch(error => {
            setTimeout(function() {
                window.location.href = "/";
            }, 20000);
        });
    }

    </script>
    WELCOMEPAGE;
}

function projectsetup(){
    echo <<<PROJECTSETUP
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .progress-bar {
            background-color: #007BFF;
            height: 8px;
            width: 50%;
        }
        .container {
            max-width: 50em;
            margin: 2em auto;
            padding: 1.25em;
            background-color: #fff;
            box-shadow: 0 0.125em 0.25em rgba(0, 0, 0, 0.1);
            border-radius: 0.5em;
            text-align: center;
        }
        h1 {
            font-size: 1.5em;
            color: #333;
        }
        p {
            font-size: 1em;
            color: #666;
        }
        .form-group {
            margin: 1.25em 0;
        }
        .form-group label {
            font-weight: bold;
        }
        .advanced-options {
            display: none;
        }
        .advanced-options-toggle {
            cursor: pointer;
            color: #007BFF;
            text-decoration: underline;
        }
        .start-button {
            background-color: #007BFF;
            color: #fff;
            border: none;
            padding: 0.625em 1.25em;
            border-radius: 0.5em;
            cursor: pointer;
            font-size: 1.125em;
        }
        .start-button:hover {
            background-color: #0056b3;
        }
    </style>
    <script>
        function toggleAdvancedOptions() {
            var options = document.querySelector(".advanced-options");
            if (options.style.display === "none" || options.style.display === "") {
                options.style.display = "block";
            } else {
                options.style.display = "none";
            }
        }
        function validateInput(input) {
            return /^[a-zA-Z0-9]+$/.test(input);
        }
    
        function toggleAdvancedOptions() {
            var options = document.querySelector(".advanced-options");
            if (options.style.display === "none" || options.style.display === "") {
                options.style.display = "block";
            } else {
                options.style.display = "none";
            }
        }
    
        function checkforproject() {
            projectname = document.getElementById("project-name").value;
    
            if (!validateInput(projectname)) {
                alert("Project name must not contain spaces or special characters.");
                return;
            }
    
            fetch('index.php', {
                method: "POST",
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ "uniqueproject": projectname })
            })
                .then(response => response.json())
                .then(response => {
                    if (response == true) {
                        createproject(projectname);
                    } else {
                        alert("Cannot create project - project already exists");
                    }
                })
                .catch(error => {
                    window.location = "index.php?page=help";
                });
        }
    
        function createproject(projectname) {
            showLoadingPopup("Creating your craft project");
        
            var dbdriver = document.getElementById("db-driver").value;
            var dbserver = document.getElementById("db-server").value;
            var dbport = document.getElementById("db-port").value;
            var dbdatabase = document.getElementById("db-database").value;
            var dbuser = document.getElementById("db-user").value;
            var dbpassword = document.getElementById("db-password").value;
            var dbschema = document.getElementById("db-schema").value;
            var dbtableprefix = document.getElementById("db-table-prefix").value;
        
            fetch('index.php', {
                method: "POST",
                headers: {
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({
                    buildcraft: true,
                    projectname: projectname,
                    dbdriver: dbdriver,
                    dbserver: dbserver,
                    dbport: dbport,
                    dbdatabase: dbdatabase,
                    dbuser: dbuser,
                    dbpassword: dbpassword,
                    dbschema: dbschema,
                    dbtableprefix: dbtableprefix
                })
            })
            .then(response => response.json())
            .then(response => {
                if (response === true) {
                    window.location = "index.php?page=projectconfig&projectname=" + projectname;
                } else {
                    window.location = "index.php?page=help";
                }
            })
            .catch(error => {
                window.location = "index.php?page=help";
            });
        }
    </script>
    <div class="progress-bar"></div>
    <div class="container">
        <h1>Craft CMS Project Setup</h1>
        <p>Enter the name of your Craft project.</p>
        <div class="form-group">
            <label for="project-name">Project Name:</label>
            <input type="text" id="project-name">
        </div>
        <p><span class="advanced-options-toggle" onclick="toggleAdvancedOptions()">Advanced Database Options</span></p>
        <div class="advanced-options">
            <div class="form-group">
                <label for="db-driver">Database Driver:</label>
                <input type="text" id="db-driver" value="mysql">
            </div>
            <div class="form-group">
                <label for="db-server">Database Server:</label>
                <input type="text" id="db-server" value="10.80.0.11">
            </div>
            <div class="form-group">
                <label for="db-port">Database Port:</label>
                <input type="text" id="db-port" value="3306">
            </div>
            <div class="form-group">
                <label for="db-database">Database Name:</label>
                <input type="text" id="db-database" value="craftdb">
            </div>
            <div class="form-group">
                <label for="db-user">Database User:</label>
                <input type="text" id="db-user" value="root">
            </div>
            <div class="form-group">
                <label for="db-password">Database Password:</label>
                <input type="text" id="db-password" value="cr4ftd4t4b4s3">
            </div>
            <div class="form-group">
                <label for="db-schema">Database Schema:</label>
                <input type="text" id="db-schema">
            </div>
            <div class="form-group">
                <label for="db-table-prefix">Table Prefix:</label>
                <input type="text" id="db-table-prefix">
            </div>
        </div>
        <button class="start-button" id="start-button" onclick="checkforproject()">Create Craft Project</button>
    </div>
    PROJECTSETUP;
}

function projectconfig(){
    echo <<<PROJECTCONFIG
            <style>
                body {
                    font-family: Arial, sans-serif;
                    background-color: #f5f5f5;
                    margin: 0;
                    padding: 0;
                }
                .progress-bar {
                    background-color: #007BFF;
                    height: 8px;
                    width: 75%;
                }
                .container {
                    max-width: 50em;
                    margin: 2em auto;
                    padding: 1.25em;
                    background-color: #fff;
                    box-shadow: 0 0.125em 0.25em rgba(0, 0, 0, 0.1);
                    border-radius: 0.5em;
                    text-align: center;
                }
                h1 {
                    font-size: 1.5em;
                    color: #333;
                }
                p {
                    font-size: 1em;
                    color: #666;
                }
                .form-group {
                    display: flex;
                    justify-content: center;
                    margin: 1.25em 0;
                    position: relative;
                }
                .form-group label {
                    font-weight: bold;
                }
                .input-error {
                    border: 2px solid #ff0000;
                }
                .info-box {
                    color: white;
                    margin-left: 2rem;
                    width: 1em;
                    height: 1em;
                    background-color: #007BFF;
                    border-radius: 50%;
                    display: flex;
                    justify-content: center;
                    align-items: center;
                    cursor: pointer;
                }
                .info-box:hover .info-text {
                    display: block;
                }
                .info-text {
                    display: none;
                    position: absolute;
                    background-color: #007BFF;
                    color: #fff;
                    padding: 0.25em 0.5em;
                    border-radius: 0.25em;
                    right: 1.5em;
                    top: 1em;
                    width: 10em;
                    text-align: left;
                }
                .start-button {
                    background-color: #007BFF;
                    color: #fff;
                    border: none;
                    padding: 0.625em 1.25em;
                    border-radius: 0.5em;
                    cursor: pointer;
                    font-size: 1.125em;
                }
                .start-button:disabled {
                    background-color: #ccc;
                    cursor: not-allowed;
                }
                .start-button:hover:disabled {
                    background-color: #ccc;
                }
            </style>

            <script>
                function validateForm() {
                    var emailInput = document.getElementById("mail-address");
                    var usernameInput = document.getElementById("username");
                    var passwordInput = document.getElementById("password");
                    var confirmPasswordInput = document.getElementById("confirm-password");
                    var sitenameInput = document.getElementById("sitename");
                    var startButton = document.getElementById("start-button");

                    var isValidEmail = /^[^\s@]+@[^\s@]+\.[^\s@]+$/.test(emailInput.value);
                    var isPasswordValid = passwordInput.value.length >= 6;
                    var isPasswordConfirmed = passwordInput.value === confirmPasswordInput.value;
                    var isUsernameValid = /^[a-zA-Z0-9\-]+$/.test(usernameInput.value);
                    var isSitenameValid = /^[a-zA-Z0-9\-]+$/.test(sitenameInput.value);

                    if (isValidEmail && isPasswordValid && isPasswordConfirmed && isUsernameValid && isSitenameValid) {
                        startButton.removeAttribute("disabled");
                        emailInput.classList.remove("input-error");
                        passwordInput.classList.remove("input-error");
                        confirmPasswordInput.classList.remove("input-error");
                        usernameInput.classList.remove("input-error");
                        sitenameInput.classList.remove("input-error");
                    } else {
                        startButton.setAttribute("disabled", "true");
                        emailInput.classList.remove("input-error");
                        passwordInput.classList.remove("input-error");
                        confirmPasswordInput.classList.remove("input-error");
                        usernameInput.classList.remove("input-error");
                        sitenameInput.classList.remove("input-error");

                        if (!isValidEmail) {
                            emailInput.classList.add("input-error");
                        }
                        if (!isPasswordValid || !isPasswordConfirmed) {
                            passwordInput.classList.add("input-error");
                            confirmPasswordInput.classList.add("input-error");
                        }
                        if (!isUsernameValid) {
                            usernameInput.classList.add("input-error");
                        }
                        if (!isSitenameValid) {
                            sitenameInput.classList.add("input-error");
                        }
                    }
                }

                function configurecraft(){
                    showLoadingPopup('setting up your craft website')
                    mail = document.getElementById("mail-address").value
                    username = document.getElementById("username").value
                    password = document.getElementById("password").value
                    pagename = document.getElementById("sitename").value
                    language = document.getElementById("language").value

                    fetch('index.php', {
                        method: "POST",
                        headers: {
                            'Accept': 'application/json',
                            'Content-Type': 'application/json'
                        },
                        body: JSON.stringify({ "setupcraft": true,
                                                "projectname": projectname,
                                                "mail": mail,
                                                "username": username,
                                                "password": password,
                                                "pagename": pagename,
                                                "language": language
                                                })
                    })
                    .then(response => response.json())
                    .then(response => {
                            console.log(response)
                            window.location = "index.php?page=finished&projectname=" + projectname
                        }
                    )
                    .catch(error => {
                        window.location = "index.php?page=help"
                    });
                }
            </script>

            <div class="progress-bar"></div>
            <div class="container">
                <h1>Craft CMS Configuration</h1>
                <div class="form-group">
                    <label for="mail-address">Mail Address:</label>
                    <input type="email" id="mail-address" oninput="validateForm()">
                    <div class="info-box">?
                        <span class="info-text">Valid email format (e.g., example@example.com)</span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="username">Username:</label>
                    <input type="text" id="username" oninput="validateForm()">
                    <div class="info-box">?
                        <span class="info-text">No special character allowed</span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="password">Password:</label>
                    <input type="password" id="password" oninput="validateForm()">
                    <div class="info-box">?
                        <span class="info-text">Minimum 6 characters</span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="confirm-password">Confirm Password:</label>
                    <input type="password" id="confirm-password" oninput="validateForm()">
                    <div class="info-box">?
                        <span class="info-text">Must match the password</span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="sitename">Site Name:</label>
                    <input type="text" id="sitename" oninput="validateForm()">
                    <div class="info-box">?
                        <span class="info-text">No special character allowed</span>
                    </div>
                </div>
                <div class="form-group">
                    <label for="language">Language:</label>
                    <select id="language">
                    <option value="de-DE" selected>German (Germany)</option>
                    <option value="en-US">English (US)</option>
                    <option value="es-ES">Spanish (Spain)</option>
                    <option value="fr-FR">French (France)</option>
                    <option value="it-IT">Italian (Italy)</option>
                    <option value="nl-NL">Dutch (Netherlands)</option>
                    <option value="pl-PL">Polish (Poland)</option>
                    <option value="tr-TR">Turkish (Turkey)</option>
                    <option value="ru-RU">Russian (Russia)</option>
                    <option value="pt-PT">Portuguese (Portugal)</option>
                    </select>
                </div>
                <button class="start-button" id="start-button" onclick="configurecraft()" disabled>Create Craft Project</button>
            </div>

    PROJECTCONFIG;
}

function finished(){
    echo <<<FINISHEDPAGE
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f5f5f5;
            margin: 0;
            padding: 0;
        }
        .progress-bar {
            background-color: #007BFF;
            height: 8px;
            width: 100%;
        }
        .container {
            max-width: 50em;
            margin: 2em auto;
            padding: 1.25em;
            background-color: #fff;
            box-shadow: 0 0.125em 0.25em rgba(0, 0, 0, 0.1);
            border-radius: 0.5em;
            text-align: center;
        }
        h1 {
            font-size: 1.5em;
            color: #333;
        }
        p {
            font-size: 1em;
            color: #666;
        }
        .success-message {
            font-size: 1.25em;
            color: #007BFF;
        }
        .finish-button {
            background-color: #007BFF;
            color: #fff;
            border: none;
            padding: 0.625em 1.25em;
            border-radius: 0.5em;
            cursor: pointer;
            font-size: 1.125em;
        }
    </style>
    <div class="progress-bar"></div>
    <div class="container">
        <h1>Installation Successful</h1>
        <p class="success-message">Craft CMS has been successfully installed.</p>
        <button class="finish-button" onclick="finish()">Finish</button>
    </div>
    <script>
        function finish(){
            showLoadingPopup("Restarting and redirecting")
            fetch('index.php', {
                method: "POST",
                headers: {
                    'Accept': 'application/json',
                    'Content-Type': 'application/json'
                },
                body: JSON.stringify({ "restart": projectname})
            })
            .then(response => response.json())
            .then(response => {
                    if(response == true){
                        setTimeout(function() {
                            window.location.href = "/";
                        }, 20000);
                    }
                }
            )
            .catch(error => {
                setTimeout(function() {
                    window.location.href = "/";
                }, 20000);
            });
        }
    </script>
    FINISHEDPAGE;
}

function error(){
    echo <<<ERROR
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Craft CMS Installation Assistant</title>
        <style>
            /* Deine vorhandenen CSS-Stile hier */
            body {
                font-family: Arial, sans-serif;
                background-color: #f5f5f5;
                margin: 0;
                padding: 0;
            }
            .progress-bar {
                background-color: red;
                height: 8px;
                width: 100%;
            }
            .container {
                max-width: 50em;
                margin: 2em auto;
                padding: 1.25em;
                background-color: #fff;
                box-shadow: 0 0.125em 0.25em rgba(0, 0, 0, 0.1);
                border-radius: 0.5em;
                text-align: center;
            }
            h1 {
                font-size: 1.5em;
                color: #333;
            }
            p {
                font-size: 1em;
                color: #666;
            }
            ul {
                text-align: left;
            }
            code {
                font-family: monospace;
                background-color: #f5f5f5;
                padding: 0.125em 0.25em;
                border: 1px solid #ccc;
            }
        </style>
    </head>
    <body>
        <div class="progress-bar"></div>
        <div class="container">
            <h1>Error Page</h1>
            <p>Oops! Something went wrong during the Craft CMS installation.</p>
            <p>Here is how to reinstall:</p>
            <ul>
                <li>Delete the following files and folders from the Craft directory if they exist:</li>
                <ul>
                    <li>composer.json</li>
                    <li>composer.lock</li>
                    <li>Vendor folder</li>
                    <li>data folder</li>
                    <li>Your project folder (replace with your project name)</li>
                </ul>
                <li>Open your command line interface (CLI) and navigate to the Craft directory.</li>
                <li>Run the following command to stop Docker Compose and remove volumes:</li>
                <code>docker-compose down --volumes</code>
                <li>After that, you can run Docker Compose again to start the installation:</li>
                <code>docker-compose up</code>
            </ul>
            <p>Have a look into the container logs in Docker for more information on what went wrong.</p>
            <p>Common installation issues may include:</p>
            <ul>
                <li>Incorrect Docker configuration</li>
                <li>incorrect database configuration</li>
                <li>out of memory (RAM or ROM)</li>
                <li>Reloading the installer pages</li>
                <li>your pc went to sleep during the installation</li>
                <li>lost internet connection</li>
                <li>composer timeout (fix: in Dockerfile set the composer process timeout in line 96 from 600 to a higher number</li>
            </ul>
            <p>If you encounter any bugs, contact me via <a href="mailto:luke.kahms@student.fh-kiel.de">e-mail</a></p>
            <p>Note that you can also <a href="https://craftcms.com/docs/4.x/installation.html">install Craft manually</a></p>
        </div>
    </body>
    </html>
    
    ERROR;
}

if ($_SERVER['REQUEST_METHOD'] != "POST") {
    //print the html head
    echo /*html*/'
    <!DOCTYPE html>
    <html lang="en">
    <head>
        <meta charset="UTF-8">
        <meta name="viewport" content="width=device-width, initial-scale=1.0">
        <title>Craft-Installation</title>
    </head>
    <body>
    <style>
        .popup-overlay {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: rgba(0, 0, 0, 0.6);
            display: flex;
            justify-content: center;
            align-items: center;
            z-index: 999;
        }
        .popup-container {
            background-color: #fff;
            padding: 1.25em;
            box-shadow: 0 0.125em 0.25em rgba(0, 0, 0, 0.1);
            border-radius: 0.5em;
            text-align: center;
        }
        h1 {
            font-size: 1.5em;
            color: #333;
        }
        p {
            font-size: 1em;
            color: #666;
        }
        .loading-animation {
            border: 8px solid #f3f3f3;
            border-top: 8px solid #3498db;
            border-radius: 50%;
            width: 40px;
            height: 40px;
            animation: spin 2s linear infinite;
            margin: auto;
        }
        @keyframes spin {
            0% { transform: rotate(0deg); }
            100% { transform: rotate(360deg); }
        }
    </style>
    <script>
        function showLoadingPopup(title) {
            var overlay = document.createElement("div");
            overlay.className = "popup-overlay";

            var popup = document.createElement("div");
            popup.className = "popup-container";

            var loadingAnimation = document.createElement("div");
            loadingAnimation.className = "loading-animation";

            var titleElement = document.createElement("h1");
            titleElement.textContent = title;

            var hint = document.createElement("p");
            hint.textContent = "This may take a few minutes... Wondering what is going on? Have a look into the docker container logs!";


            popup.appendChild(titleElement);
            popup.appendChild(loadingAnimation);
            popup.appendChild(hint);


            overlay.appendChild(popup);


            document.body.appendChild(overlay);

            overlay.addEventListener("click", function (event) {
                event.stopPropagation();
            });
        }

        function validateInput(input) {
            if (input.trim() === "") {
                alert("input must not be empty");
                return false;
            }

            const regex = /^[a-zA-Z\-]+$/;
            if (!regex.test(input)) {
                alert("only characters a-z / A-Z allowed");
                return false;
            }

            return true;
        }
        ';
if (isset($_GET['projectname'])) {
    echo("projectname='" . $_GET['projectname'] . "'");
}

echo '

    </script>

';
    switch ($page) {
        case 'welcome':
            welcomepage();
            break;
        case 'projectsetup':
            projectsetup();
            break;
        case 'projectconfig':
            projectconfig();
            break;
        case 'finished':
            finished();
            break;
        case 'help':
            error();
            break;

        default:
            # code...
            break;
    }

    echo /*html*/'
        
    </body>
    </html>
    ';
}

?>