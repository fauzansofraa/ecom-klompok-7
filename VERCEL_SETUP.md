# Vercel setup - LZFJ Shoes Store

## 1. GitHub
Do not commit `config/config.local.php` or `.env`.

## 2. Vercel Environment Variables
Project Settings > Environment Variables:

Required:
- DB_HOST
- DB_PORT
- DB_NAME
- DB_USER
- DB_PASSWORD
- GOOGLE_CLIENT_ID
- GOOGLE_CLIENT_SECRET
- MIDTRANS_SERVER_KEY
- MIDTRANS_CLIENT_KEY
- MIDTRANS_IS_PRODUCTION=false

Optional:
- BASE_URL=https://YOUR-PROJECT.vercel.app
- GOOGLE_REDIRECT_URI=https://YOUR-PROJECT.vercel.app/google-callback.php

After saving variables, redeploy. Vercel applies changed environment variables to new deployments.

## 3. Google OAuth
Authorized redirect URI:
https://YOUR-PROJECT.vercel.app/google-callback.php

The redirect URI must match exactly.

## 4. Midtrans Sandbox
Use Sandbox Client Key and Sandbox Server Key while:
MIDTRANS_IS_PRODUCTION=false

The Server Key must remain secret. The Client Key may be exposed to browser code by Midtrans design.

## 5. Database
Vercel containers are stateless. MySQL must be hosted externally (not 127.0.0.1 on your PC).
Import `database/lzfj_shoes_store.sql` into the external MySQL database and use its connection values in Vercel.

## 6. Local Laragon
Copy:
config/config.local.example.php
to:
config/config.local.php

Then fill the local Google and Midtrans Sandbox credentials there.
