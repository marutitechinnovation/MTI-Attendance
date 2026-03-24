# Employee PWA QA Checklist

Use this checklist before production rollout of the employee PWA.

## 1) Access and Install

- Open `http://localhost:8082/employee` (or `http://localhost:8082/login` then **Open employee web app**) on Android Chrome and desktop Chrome.
- Confirm app shell loads and no blank screen appears.
- Confirm install button appears where supported.
- Install the PWA and relaunch from home screen icon.
- Confirm the app opens directly to employee flow.

## 2) Authentication

- Login with valid employee credentials.
- Verify invalid username/password shows clear error.
- Verify logout requires confirmation.
- Verify logged-out state uses URL `/employee` (not `/employee/login`, which redirects to `/login`).
- Reload page after login and confirm session persists.

## 3) Navigation and Routing

- Open each deep link directly:
  - `/employee/dashboard`
  - `/employee/attendance`
  - `/employee/calendar`
  - `/employee/profile`
- Confirm correct tab is active after load.
- Confirm bottom navigation updates URL and screen.

## 4) Attendance Scan Flow

- In Attendance -> Scan tab, start camera and scan valid QR.
- Confirm geolocation prompt appears (first time).
- Confirm next-action strip changes with scan sequence.
- Confirm ambiguous step allows choosing `break_start` or `check_out`.
- Confirm success modal appears and timeline refreshes.
- Confirm scanner cooldown avoids duplicate submissions.

## 5) Error and Edge Cases

- Deny camera permission and confirm helper message is shown.
- Deny location permission and confirm error dialog/message.
- Turn internet off and try scan submit; confirm offline message.
- Use invalid QR token and confirm failure feedback.
- Scan outside geofence and confirm flagged warning modal/chip.

## 6) Data and UI Checks

- Dashboard summary values (in/out/break/worked/status) look correct.
- Attendance history subtab shows grouped monthly records.
- Status chip per day is correct (default labels: `Working`, `On Break`, `Shift Complete`, or your customized labels from Settings).
- Calendar tab loads holiday list.
- Profile tab shows employee details correctly.

## 7) Offline and Recovery

- Turn internet off and confirm offline banner appears.
- Re-enable internet and click retry; confirm data refreshes.
- Reload app while offline and confirm shell still renders.

## 8) Security/Release Minimum

- Confirm app runs under HTTPS in staging/production.
- Confirm API scan endpoint remains network-only (no cached writes).
- Confirm sensitive employee data is not exposed in console logs.
- Confirm admin panel routes remain unchanged and functional.

## 9) Smoke Test After Deploy

- Login
- One check-in scan
- One break start + break end
- One check-out
- Verify records in admin attendance page
- Verify no critical frontend errors in browser console

## 10) Admin branding, map, and labels (Settings)

Prerequisite: run `php spark migrate` so new `settings` keys exist (see migration `AddBrandingMapStatsSettings`).

- Open **Admin → Settings** and save; confirm the green success alert appears.
- Under **Employee PWA — name & colors**, change app name, short name, theme/background/accent hex. Reload `/employee` and confirm login title, meta `theme-color`, and colors update (icon/logo assets unchanged).
- Open `GET /manifest.webmanifest` (same origin) and confirm `name`, `short_name`, `theme_color`, and `background_color` match Settings.
- Under **Live map**, keep **OpenStreetMap**, open **Admin → Map**, confirm tiles load.
- Set **Custom** tile URL (with `{z}` `{x}` `{y}` and `{apikey}` if needed), save a key, reload Map; confirm tiles load. Toggle **Remove saved map API key** and save; confirm custom tiles fail or fall back as expected until a key is set again.
- Change **scan & status labels** in Settings; reload employee PWA dashboard and attendance flow and confirm summary headers, timeline text, modals, and status chips use the new wording.
- Hard-reload or wait for service worker update after manifest changes; re-install PWA if the home screen name does not refresh immediately.

