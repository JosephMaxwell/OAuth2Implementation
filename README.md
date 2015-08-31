#OAuth Tutorial

For me, OAuth used to be a bad word. It seemed to be this giant monster that was to be avoided at all costs. It just didn't make any sense.

However, my goal was to decipher it for myself and present it to the local KC PHP user group. This is that project.

##How to begin:

* Go to your terminal and `cd` to where you would like this project to reside.
* Type `git clone https://github.com/JosephMaxwell/OAuth2Implementation.git` + Enter
* Type `git checkout tutorial` (if you want to follow along).
* Setup a MAMP or Vagrant box. **It needs to have a `https` address.**

##Using Google API Console

1. Go to http://console.developers.google.com/
2. Sign in.
3. From the menu at the top of the screen, select Create Project
  * Title your project
  * Click Create
  * Click APIs & auth on the left column (it may take a moment or two for the screen to update)
4. Select APIs
  * Search for Drive
  * Click the Drive API link in the search results
  * Click the blue Enable API button
5. Click Credentials
  * In the top bar, select "OAuth consent screen".
    * Fill in available fields and Save 
  * Click the blue Add Credentials button (OAuth 2.0 client ID)
    * Choose Web Application
    * Add your local server's domain name into **Authorized JavaScript origins**.
    * Add your URL into **Authorized redirect URIs**: (exactly https://[domain_name]/app/callback).
    * Click create
    * Store client ID and client secret in a safe place.
  * Next to the client ID just created, click the download arrow and save file as `client_secrets.json` in project folder (next to `index.php`).
  * Change the token URI element to be: "https://www.googleapis.com/oauth2/v3/token"
