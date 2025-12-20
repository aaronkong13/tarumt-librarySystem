# 🔍 Which .env File is Used? - Quick Guide

## Understanding .env Files

You have **3** .env files:
```
.env            ← Default file
.env.frontend   ← Frontend configuration
.env.backend    ← Backend configuration
```

---

## 🎯 Which File is Used?

Laravel uses the file specified in the `--env` parameter:

### Scenario 1: Frontend Server (Port 8000)
```bash
php artisan serve --port=8000 --env=.env.frontend
```
**Uses:** `.env.frontend`
**Config inside:**
```env
APP_NAME="LibrarySystem-Frontend"
APP_URL=http://localhost:8000
API_URL=http://localhost:8001
```

---

### Scenario 2: Backend Server (Port 8001)
```bash
php artisan serve --port=8001 --env=.env.backend
```
**Uses:** `.env.backend`
**Config inside:**
```env
APP_NAME="LibrarySystem-Backend"
APP_URL=http://localhost:8001
API_URL=http://localhost:8001
```

---

### Scenario 3: Default (No --env specified)
```bash
php artisan serve --port=8000
```
**Uses:** `.env` (the default file)
**Config inside:**
```env
APP_NAME=Laravel
APP_URL=http://localhost:8000
API_URL=http://localhost:8001
```

---

## ✅ Recommended Setup (What You Should Do)

### Terminal 1 - Frontend
```bash
cd c:\Users\Wengh\OneDrive\Desktop\IP_assignment\tarumt-librarySystem
php artisan serve --port=8000 --env=.env.frontend
```

### Terminal 2 - Backend
```bash
cd c:\Users\Wengh\OneDrive\Desktop\IP_assignment\tarumt-librarySystem
php artisan serve --port=8001 --env=.env.backend
```

---

## 🔍 How to Verify Which .env is Active

After starting the server, run this in a **third terminal**:

```bash
php artisan tinker --env=.env.frontend
>>> config('app.name')
# Should show: "LibrarySystem-Frontend"
>>> config('app.url')
# Should show: "http://localhost:8000"
>>> exit
```

Or for backend:
```bash
php artisan tinker --env=.env.backend
>>> config('app.name')
# Should show: "LibrarySystem-Backend"
>>> config('app.url')
# Should show: "http://localhost:8001"
>>> exit
```

---

## 📊 Visual Guide

```
┌─────────────────────────────────────────────────────────┐
│  Command: php artisan serve --port=8000 --env=X         │
└─────────────────────────────────────────────────────────┘
                         │
                         ↓
        ┌────────────────┴────────────────┐
        │                                 │
    --env=.env.frontend            --env=.env.backend
        │                                 │
        ↓                                 ↓
  .env.frontend                     .env.backend
  (Port 8000)                       (Port 8001)
  Frontend Server                   Backend Server
```

---

## 🎯 Key Points

1. **The `--env` parameter tells Laravel which file to use**
   - `--env=.env.frontend` → Uses `.env.frontend`
   - `--env=.env.backend` → Uses `.env.backend`
   - No `--env` → Uses `.env` (default)

2. **Port number and .env file are INDEPENDENT**
   - You specify port with `--port=8000`
   - You specify .env file with `--env=.env.frontend`
   - They don't automatically match!

3. **You MUST specify BOTH `--port` AND `--env`** for clarity:
   ```bash
   # ✅ CORRECT
   php artisan serve --port=8000 --env=.env.frontend
   php artisan serve --port=8001 --env=.env.backend
   
   # ⚠️ CONFUSING (uses default .env but port 8000)
   php artisan serve --port=8000
   
   # ❌ WRONG (uses .env.backend but port 8000 - mismatch!)
   php artisan serve --port=8000 --env=.env.backend
   ```

---

## 📝 Summary Table

| Command | Uses File | Port | Server Type |
|---------|-----------|------|-------------|
| `php artisan serve --port=8000 --env=.env.frontend` | `.env.frontend` | 8000 | Frontend |
| `php artisan serve --port=8001 --env=.env.backend` | `.env.backend` | 8001 | Backend |
| `php artisan serve --port=8000` | `.env` | 8000 | Default |
| `php artisan serve` | `.env` | 8000 | Default |

---

## 🚀 Your Current Servers

When you run the servers, check the terminal output:

**Terminal 1 (Frontend):**
```
php artisan serve --port=8000 --env=.env.frontend

Laravel development server started: http://127.0.0.1:8000
```
→ Using `.env.frontend`

**Terminal 2 (Backend):**
```
php artisan serve --port=8001 --env=.env.backend

Laravel development server started: http://127.0.0.1:8001
```
→ Using `.env.backend`

---

**Remember:** The `--env` parameter tells Laravel which config file to read!
