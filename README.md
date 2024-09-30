# Easy Craft On Docker Installation Assistant

A streamlined solution to install CraftCMS using Docker. This installer is intended for **development environments** only and automates the process, allowing developers to quickly set up CraftCMS. This project is **not suitable** for production environments.

## Table of Contents
- [Requirements](#requirements)
- [Installation](#installation)
- [Database Configuration](#database-configuration)
- [Accessing Your CraftCMS Site](#accessing-your-craftcms-site)
- [Handling Errors](#handling-errors)
- [Collaborating on a Craft Project](#collaborating-on-a-craft-project)
- [Packaging a Craft Project](#packaging-a-craft-project)
- [Restoring a Craft Project](#restoring-a-craft-project)
- [Known Limitations](#known-limitations)
- [FAQ](#faq)
- [Bug Reports](#bug-reports)

## Requirements

- Docker must be installed and running on your system.
- Ensure you have sufficient resources: 8GB of RAM and adequate disk space are recommended.

## Installation

1. **Unpack the files**: Download and extract the `.zip` archive containing the necessary files: `Dockerfile`, `docker-compose.yml`, and the `craft` folder.

2. **Navigate to the project directory**: Open a terminal or command prompt and navigate to the directory containing the `Dockerfile` and `docker-compose.yml`.
    ```bash
    cd "your/project/directory"
    ```

3. **Build and start the Docker containers**:
    ```bash
    docker-compose up -d --build
    ```
   > Note: Docker might require permission to access files on your system.

4. **Access the installer**:
    - Open Docker and find the Craft PHP container.
    - Click on the three dots, and choose `Open in Browser`.
    - Alternatively, open your browser and go to `http://localhost:3380`.

5. **Start the installation**:
    - Confirm that you understand this installer is for development purposes only.
    - Click on **Start Installation**.

6. **Create your CraftCMS project**:
    - Enter a project name and configure your database if needed via the **Advanced Database Options**.
    - Click **Create Craft Project**.

7. **Finalize the installation**:
    - After entering your CraftCMS site configuration, click **Finish** to complete the setup.
    - You will be redirected to your new CraftCMS site.

## Database Configuration

By default, the installer creates a database with the following configuration:
- **Username**: `root`
- **Password**: `cr4ftd4t4b4s3`
- **IP Address**: `10.80.0.11`
- **Database Name**: `craftdb`
- **Port**: `3381` (Host) / `3306` (Container)

These settings are pre-configured and do not need to be manually entered during the CraftCMS installation.

If you wish to change these settings, modify the `docker-compose.yml` file before running the installation.

## Accessing Your CraftCMS Site

Once the installation is complete:
- Navigate to `http://localhost:3380` to view your CraftCMS site.
- To access the admin panel, visit `http://localhost:3380/admin`.

## Handling Errors

If the installation fails:
1. Run the following command to stop the containers:
    ```bash
    docker-compose down
    ```
2. Delete the following files/folders from the `craft` directory:
    - `composer.json`
    - `composer.lock`
    - `vendor` folder
    - Project folder (named after your project)
    - `setup`
    - `installed`
3. Restart your computer and repeat the installation steps.

Common causes of installation failure:
- Incorrect database configuration
- Insufficient system resources (8GB RAM recommended)
- Lack of file permissions
- Docker setup issues

### Increasing Upload Limit

To increase the default upload limit (32MB):
1. Open the `Dockerfile` in a text editor.
2. Adjust lines 66 and 67 to set the desired file upload size in MB.

If CraftCMS is already installed:
1. Access the Craft PHP container's CLI in Docker.
2. Run the following commands:
    ```bash
    cd /usr/local/etc/php
    sudo sed -i "s/^upload_max_filesize = .*/upload_max_filesize = ?M/" "php.ini"
    sudo sed -i "s/^post_max_size = .*/post_max_size = ?M/" "php.ini"
    ```

## Collaborating on a Craft Project

To collaborate on a CraftCMS project:
- Use Git(Hub) or other version control systems to sync the project files.
- **Do not sync the `data` folder**. Instead, use a hosted database solution to share the database.
- Alternatively, package the project (see the next section) and share it with your collaborator.

## Packaging a Craft Project

To package a CraftCMS project for distribution:
1. Ensure your project folder contains the following files:
    - `Dockerfile`
    - `docker-compose.yml`
    - `data` folder
    - `craft` folder (only include the `vendor` folder and your project folder)
2. Zip these files into a single archive.
3. Share the `.zip` archive with your collaborator.

## Restoring a Craft Project

To restore a CraftCMS project:
1. Extract the `.zip` archive into your project directory.
2. Follow the installation steps with the extracted files.
3. If the project was created with version 4.0 or later, it will automatically restore.

If not, choose **Restore Project** instead of starting a new installation.

## Known Limitations

This installer is **not suitable** for production environments due to:
- Simplified installation processes that might bypass crucial configuration steps.
- Pre-configured database settings that may not meet security standards.
- The `www-data` user having elevated permissions.

Read more about these limitations in the [Production Environment](#known-limitations) section.

## FAQ

### How do I access my CraftCMS site?

Once installed, you can access your site by:
- Navigating to `http://localhost:3380` in a browser.
- Alternatively, in Docker, click on the three dots next to the Craft PHP container and select **Open in Browser**.

### How do I access the admin panel of my site?

Follow the same steps to access your site, and add `/admin` to the URL:
- `http://localhost:3380/admin`

### Where can I find the site’s files?

Your CraftCMS files are located in the directory from which you ran the `docker-compose up` command:
- **Files**: `craft` directory under a folder with your project’s name.
- **Database**: `data` folder under the installation directory.

In the container, the files are located in `/var/www/html`.

### How do I create a filesystem in CraftCMS?

To create a filesystem in CraftCMS:
- Go to **Settings** > **Filesystems**.
- For **Basepath**, use the container's path (not the host system's). For example, use `@webroot/my-folder/subfolder`.

### How do I increase the upload limit?

If you haven't installed Craft yet:
- Open the `Dockerfile` and modify lines 66 and 67 to adjust the upload size in megabytes.

If CraftCMS is already installed:
1. Open the Craft PHP container's CLI in Docker.
2. Run:
    ```bash
    cd /usr/local/etc/php
    sudo sed -i "s/^upload_max_filesize = .*/upload_max_filesize = ?M/" "php.ini"
    sudo sed -i "s/^post_max_size = .*/post_max_size = ?M/" "php.ini"
    ```

### What should I do if the installation fails?

Follow these steps:
1. Stop the containers using:
    ```bash
    docker-compose down
    ```
2. Delete the following files/folders:
    - `composer.json`, `composer.lock`, `vendor` folder, project folder (your project’s name), `setup`, and `installed`.
3. Restart your computer and repeat the installation.

### What are my database login details?

The installer sets up a default database:
- **Username**: `root`
- **Password**: `cr4ftd4t4b4s3`
- **Database Name**: `craftdb`
- **IP Address**: `10.80.0.11`
- **Host Port**: `3381`
- **Container Port**: `3306`

### How do I open PHPMyAdmin?

PHPMyAdmin is installed by default. Access it similarly to the CraftCMS site:
- Navigate to `http://localhost:3381` or find the PHPMyAdmin container in Docker and open it in the browser.

### How can I collaborate with others on a CraftCMS project?

Use version control systems like GitHub to sync files (except the `data` folder). For collaboration, you may also use an external database that both parties can connect to.

### How do I package a Craft project for distribution?

Zip the following files and folders:
- `Dockerfile`, `docker-compose.yml`, `data` folder, `craft` folder (with `vendor` and project folder).
Make sure the directory structure remains intact.

### How do I restore a Craft project?

Extract the received `.zip` archive, follow the installation steps, and choose **Restore Project** instead of a new installation.

### Can I customize the database configuration?

Yes, modify the `docker-compose.yml` file to change database credentials or link to an external database. Update the advanced database settings in the installer during setup.

## Bug Reports

This installer was developed by **Luke Kahms** as part of a course at the **Fachhochschule Kiel**. Please report any bugs or issues to:
[luke.kahms@fh-kiel.de](mailto:luke.kahms@fh-kiel.de)

---

Made with ❤️ in Kiel.
