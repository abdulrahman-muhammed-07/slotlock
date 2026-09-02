# web

Small Angular standalone client for the booking API. It exists to prove the
fullstack wiring, not to look good: sign in, list resources, view a resource's
availability for a date, book a slot, and show the 409 when the slot is taken.

```bash
npm ci
npm start          # http://localhost:4200, expects the API on :8000
npm run lint
npm run build
npm test           # Karma/Jasmine, needs Chrome (CHROME_BIN)
```

The API base URL is in `src/environments/environment.ts`.
