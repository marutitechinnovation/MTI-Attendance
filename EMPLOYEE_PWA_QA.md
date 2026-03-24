# Employee PWA QA Checklist

Use this checklist before production rollout of the employee PWA.

## 1) Access and Install

- Open `http://localhost:8082/employee/login` on Android Chrome and desktop Chrome.
- Confirm app shell loads and no blank screen appears.
- Confirm install button appears where supported.
- Install the PWA and relaunch from home screen icon.
- Confirm the app opens directly to employee flow.

## 2) Authentication

- Login with valid employee credentials.
- Verify invalid username/password shows clear error.
- Verify logout requires confirmation.
- Verify logged-out state redirects to `/employee/login`.
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
- Status chip per day is correct (`Working`, `On Break`, `Shift Complete`, `Flagged`).
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

