# Quick Start Guide: Running Frontend & Backend Servers

## 🚀 Start Both Servers

Open **TWO** PowerShell terminals and run:

### Terminal 1: Frontend Server
```powershell
cd c:\Users\Wengh\OneDrive\Desktop\IP_assignment\tarumt-librarySystem
php artisan serve --port=8000 --env=.env.frontend
```

### Terminal 2: Backend Server
```powershell
cd c:\Users\Wengh\OneDrive\Desktop\IP_assignment\tarumt-librarySystem
php artisan serve --port=8001 --env=.env.backend
```

---

## 🌐 Access URLs

### Frontend (Web Pages)
- **Books**: http://localhost:8000/books
- **Users**: http://localhost:8000/users
- **Borrowings**: http://localhost:8000/borrowings
- **Dashboard**: http://localhost:8000/dashboard

### Backend (API Endpoints)
- **Books API**: http://localhost:8001/api/books
- **Users API**: http://localhost:8001/api/users
- **Borrowings API**: http://localhost:8001/api/borrowings

### ❌ BLOCKED (Will return 403 error)
- http://localhost:8000/api/books
- http://localhost:8000/api/users
- http://localhost:8000/api/*

---

## 📊 What's Running Where?

| Service | Port | URL Pattern | Purpose |
|---------|------|-------------|---------|
| **Frontend** | 8000 | `/books`, `/users` | Web pages, views |
| **Backend** | 8001 | `/api/books`, `/api/users` | JSON API |

---

## ✅ Verify It's Working

### Test Frontend (Should show HTML)
```powershell
curl http://localhost:8000/books
```

### Test Backend (Should show JSON)
```powershell
curl http://localhost:8001/api/books
```

### Test Blocking (Should show 403 error)
```powershell
curl http://localhost:8000/api/books
```

---

## 🛑 Stop Servers

Press `Ctrl+C` in each terminal window.

---

## 📝 Understanding the Setup

### Environment Files
- **`.env`** → Default (frontend mode, port 8000)
- **`.env.frontend`** → Explicit frontend configuration
- **`.env.backend`** → Explicit backend configuration

### Key Differences
```
Frontend (.env.frontend):
  APP_URL=http://localhost:8000
  API_URL=http://localhost:8001  ← Points to backend

Backend (.env.backend):
  APP_URL=http://localhost:8001
  API_URL=http://localhost:8001  ← Self-reference
```

---

## 🔧 Troubleshooting

### Problem: Port already in use
```
Error: Address already in use
```
**Solution**: Kill the process or use different ports:
```powershell
# Find process using port 8000
netstat -ano | findstr :8000

# Kill the process (replace PID with actual process ID)
taskkill /PID <PID> /F
```

### Problem: Which .env is active?
```powershell
php artisan tinker
>>> config('app.name')
```
Should show:
- Frontend: `"LibrarySystem-Frontend"`
- Backend: `"LibrarySystem-Backend"`

---

## 🎯 Summary

1. **Run TWO separate servers** (frontend + backend)
2. **Frontend (8000)**: Web pages
3. **Backend (8001)**: API endpoints
4. **Never access** `/api/*` on port 8000
5. **Use environment-specific** `.env` files

---

**Ready to go!** 🎉
