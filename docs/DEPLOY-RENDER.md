# NEO-LABS — Render'ga deploy qilish

Loyiha Render (Docker) uchun tayyorlangan: `Dockerfile`, `docker/`, `render.yaml`.
Ma'lumotlar bazasi — mavjud Supabase (Singapur). App ham **Singapur** regionida ishga tushadi
(baza bilan bir regionda → so'rovlar tez).

## 1. Kodni GitHub'ga yuklash
Git repo tayyor va commit qilingan (`main` branch). GitHub'da **bo'sh** repo yarating, so'ng:

```bash
git remote add origin https://github.com/<foydalanuvchi>/<repo>.git
git push -u origin main
```

## 2. Render'da servis yaratish (Blueprint)
1. https://dashboard.render.com → **New +** → **Blueprint**
2. GitHub repongizni ulang → Render `render.yaml` ni avtomatik o'qiydi
3. **Apply** bosing

## 3. Maxfiy env o'zgaruvchilarni kiritish
Render'da servis → **Environment** bo'limida quyidagilarni `.env` faylingizdan nusxalab qo'ying
(`render.yaml` da `sync: false` bo'lganlari):

| Kalit | Qiymat |
|---|---|
| `APP_KEY` | `.env` dagi `base64:...` |
| `APP_URL` | dastlab `https://<servis>.onrender.com` (deploydan keyin aniqlanadi) |
| `DB_HOST` | `aws-0-ap-southeast-1.pooler.supabase.com` |
| `DB_USERNAME` | `postgres.diquverxdvkyqxueswvg` |
| `DB_PASSWORD` | `.env` dagi parol |
| `GEMINI_API_KEY` | `.env` dan |
| `PEXELS_API_KEY` | `.env` dan |
| `ANTHROPIC_API_KEY` | `.env` dan (ixtiyoriy) |
| `TELEGRAM_BOT_TOKEN` | `.env` dan |
| `TELEGRAM_CHAT_ID` | `.env` dan |

(Static qiymatlar — `APP_ENV=production`, `DB_CONNECTION=pgsql`, `AI_PROVIDER=gemini` va h.k. — `render.yaml` da allaqachon bor.)

## 4. Deploy
Render avtomatik build qiladi (`composer install` + nginx/php-fpm). ~3-5 daqiqa.
Bitgach: `https://<servis>.onrender.com/login` → `admin@neo-labs.uz` / `password`.

Deploydan keyin **APP_URL** ni haqiqiy Render manziliga o'zgartiring (yoki domeningizga) va qayta deploy bo'ladi.

## Muhim eslatmalar
- **Rasm yuklash (bepul tarifda vaqtinchalik):** repodagi mavjud rasmlar (mahsulot/maqola/hamkor) ishlaydi.
  Lekin deploydan keyin **yangi yuklangan** rasmlar (admin upload + AI rasmlari) qayta deployда yo'qoladi (Render free — efemer disk).
  Doimiy saqlash uchun: Render **Starter ($7/oy) + Persistent Disk**, yoki rasmlarni **Supabase Storage**ga ko'chirish (men qila olaman).
- **Tezlik:** app va baza ikkalasi Singapurda → sahifalar tez ochiladi. Foydalanuvchi brauzeri bilan server orasida bitta uzoq masofa qoladi (bu sezilarli emas).
- **Domen:** neo-labs.uz ni Render'ga ulash uchun Render → Settings → Custom Domain → DNS yozuvlarini qo'shasiz.
- **Config o'zgarsa:** Render env o'zgartirilsa avtomatik qayta deploy bo'ladi.
