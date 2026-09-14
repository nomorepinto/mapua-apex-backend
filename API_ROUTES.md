# API routes

All v1 routes sit under `/api/v1`. Collections and single resources are wrapped in Laravel's `data` key.

## Auth

Every v1 route needs both headers:

```http
X-Api-Key: <API_TOKEN_STUDENT | API_TOKEN_SIGNATORY | API_TOKEN_ADMIN>
Authorization: Bearer <Cognito ID token>
```

- Use the matching role key, **or** `API_TOKEN_ADMIN` on student and signatory routes. A Bearer token alone is not enough.
- Send the Cognito **ID** token, not the access token. Custom attributes are not on access tokens by default (Amplify: `session.tokens.idToken`). After you change a Cognito attribute, the user must sign in again so the new token includes it.
- `cognito:groups` must include the route role (`student`, `signatory`, or `admin`). The `admin` group may call student and signatory routes.
- **Student** JWT needs `custom:organization_id` (plain `organization_id` is also accepted) set to the Dynamo org UUID from `POST /api/v1/admins/organizations`, optionally prefixed `ORGANIZATION#`. All student routes use that claim only. `X-Organization-Id` is ignored. Missing claim → `401`. Another org’s event → `404`.
- **Signatory** JWT needs `custom:signatory_id` (plain `signatory_id` is also accepted) set to the Dynamo signatory id from `POST /api/v1/admins/signatories`, optionally prefixed `SIGNATORY#`. All signatory routes use that claim only. `X-Signatory-Id` is ignored. Missing claim → `401`. Submission not on their desk → `404`.
- **Admin** JWT does not need those claims. Admin list/show routes are global. To call a student or signatory route as admin, send `X-Organization-Id` and/or `X-Signatory-Id` when the JWT does not already carry the matching claim.

Wrong or missing key, JWT, group, or required claim → `401`. Identity provider JWKS down → `503`. Over limit → `429`. Cross-org / off-desk access → `404` (not `403`).

In **PUT** examples, lines marked `← CHANGE THIS` are the fields you typically edit. Send the full body anyway; the API replaces the SAAF document, not a patch of one key.

---

## Student routes

`X-Api-Key: <API_TOKEN_STUDENT>`

Scoped to the JWT org (`custom:organization_id`). List and deadlines return only that org. Event paths return `404` if the event belongs to another org.

| Method | Endpoint | Example JSON |
| :---- | :---- | :---- |
| **GET** | `/api/v1/students/submissions` | **Response `200`** — list for the JWT org only; other orgs are omitted. <pre>{<br>  "data": [<br>    {<br>      "event_id": "e001",<br>      "submission_id": "s001",<br>      "submission_type": "saaf",<br>      "sent_at": "2026-09-10T14:00:00Z",<br>      "status": "pending",<br>      "current_signatory": "adv001",<br>      "activity_classification": {<br>        "activity_type": "extra-curricular",<br>        "total_org_members": 42<br>      },<br>      "proponents": [ { "id": "p001", "first_name": "Nicole", "last_name": "Santos", "email_address": "nsantos@mymail.mapua.edu.ph" } ],<br>      "activity_details": {<br>        "title_and_nature": "Hack Night: Intro to Web Dev",<br>        "venue": "MPH 2nd Floor",<br>        "date_of_event": "2026-10-05"<br>      },<br>      "institutional_alignment": { "...": "..." },<br>      "detailed_budget_proposal": { "grand_total": 3000 },<br>      "venue_reservation": { "has_reservation": true }<br>    }<br>  ]<br>}</pre> |
| **GET** | `/api/v1/students/events/{event}/submissions/{submission}` | **Response `200`** — one full SAAF. Path uses both ids because PK is `EVENT#` + `SUBMISSION#`. <pre>{<br>  "data": {<br>    "event_id": "e001",<br>    "submission_id": "s001",<br>    "submission_type": "saaf",<br>    "sent_at": "2026-09-10T14:00:00Z",<br>    "status": "pending",<br>    "current_signatory": "adv001",<br>    "activity_classification": {<br>      "activity_type": "extra-curricular",<br>      "total_org_members": 42<br>    },<br>    "proponents": [ { "id": "p001", "position_title": "President", "first_name": "Nicole", "middle_name": "R", "last_name": "Santos", "suffix": "", "student_number": "2021-00123", "program_and_year": "BSCS-3", "date_of_submission": "2026-09-10", "department": "CCIS", "position_of_applicant": "President", "org_or_course_section": "Mapua Computing Society", "contact_number": "09171234567", "email_address": "nsantos@mymail.mapua.edu.ph", "facebook_link": "fb.com/nicole.santos" } ],<br>    "activity_details": {<br>      "title_and_nature": "Hack Night: Intro to Web Dev",<br>      "description": "A beginner-friendly hackathon night.",<br>      "objectives": "Introduce first-year students to web development.",<br>      "venue": "MPH 2nd Floor",<br>      "date_of_event": "2026-10-05",<br>      "day_of_event": "Monday",<br>      "time_of_event": "17:00",<br>      "expected_participants": 60,<br>      "individual_contribution": 0,<br>      "proposed_budget": 5000<br>    },<br>    "institutional_alignment": {<br>      "mission_statements": { "competitive": true, "research": false, "solutions": true },<br>      "core_values_explanation": "Promotes collaboration and innovation.",<br>      "peo_explanation": "Builds technical competency outside curriculum.",<br>      "sdg_explanation": "Supports SDG 4: Quality Education."<br>    },<br>    "detailed_budget_proposal": {<br>      "items": [ { "item_no": "1", "unit": 1, "quantity": 60, "price_per_unit": 50, "total": 3000 } ],<br>      "grand_total": 3000<br>    },<br>    "venue_reservation": {<br>      "has_reservation": true,<br>      "equipment_requested": { "monoblock_chairs": true, "whiteboards": false, "tables": true, "rostrum": false, "flags_with_stand": false, "panel_boards": false, "others_specified": "" },<br>      "general_facilities": { "purpose": "Hackathon venue", "items": [ { "item": "MPH", "date_of_use": "2026-10-05", "time_of_use": "17:00", "location": "MPH 2F" } ] },<br>      "function_rooms": { "purpose": "", "items": [] },<br>      "audiovisual_equipment": { "purpose": "Projector for demo", "items": [ { "date_needed": "2026-10-05", "time_needed": "17:00", "equipment_needed": "Projector", "remarks": "1 unit" } ] }<br>    }<br>  }<br>}</pre> |
| **POST** | `/api/v1/students/submissions` | **Request body** — if `event_id` does not exist yet, the API creates that event under the JWT org. First desk is always the org Adviser. Sequence is Adviser → Dean (when `activity_type` is `co-curricular`) → OSAAR → CDM (when `has_reservation` is true). **422** if those desks are not assigned. **Response `201`** is the same shape as GET one (ids filled in). Full required body is in [SAAF create body](#saaf-create-body) below. Compact: <pre>{<br>  "event_id": "e001",<br>  "submission_type": "saaf",<br>  "activity_classification": {<br>    "activity_type": "extra-curricular",<br>    "total_org_members": 42<br>  },<br>  "proponents": [ { "...": "see SAAF create body" } ],<br>  "activity_details": { "...": "see SAAF create body" },<br>  "institutional_alignment": { "...": "see SAAF create body" },<br>  "detailed_budget_proposal": { "...": "see SAAF create body" },<br>  "venue_reservation": { "has_reservation": true, "...": "see SAAF create body" }<br>}</pre> |
| **PUT** | `/api/v1/students/events/{event}/submissions/{submission}` | **Request body** — same SAAF fields as POST **except omit `event_id`** (it is in the URL). Allowed when `status` is `pending` or `denied`, not `approved`. Routing re-runs if `activity_type` or `has_reservation` change. **Response `200`** is the updated GET-one object (`status` back to `pending`, `current_signatory` reset to Adviser). Highlight typical resubmit edits: <pre>{<br>  "submission_type": "saaf",<br>  "activity_classification": {<br>    "activity_type": "extra-curricular",   ← CHANGE THIS to re-route (Dean hop for co-curricular)<br>    "total_org_members": 42<br>  },<br>  "proponents": [ { "...": "same as create" } ],<br>  "activity_details": {<br>    "title_and_nature": "Hack Night: Intro to Web Dev (revised)",  ← CHANGE THIS<br>    "description": "Updated description after denial.",            ← CHANGE THIS<br>    "objectives": "Introduce first-year students to web development.",<br>    "venue": "MPH 2nd Floor",<br>    "date_of_event": "2026-10-12",                                 ← CHANGE THIS<br>    "day_of_event": "Monday",<br>    "time_of_event": "17:00",<br>    "expected_participants": 60,<br>    "individual_contribution": 0,<br>    "proposed_budget": 4500                                        ← CHANGE THIS<br>  },<br>  "institutional_alignment": { "...": "same as create" },<br>  "detailed_budget_proposal": {<br>    "items": [ { "item_no": "1", "unit": 1, "quantity": 60, "price_per_unit": 40, "total": 2400 } ],  ← CHANGE THIS<br>    "grand_total": 2400                                              ← CHANGE THIS<br>  },<br>  "venue_reservation": {<br>    "has_reservation": true,                                         ← CHANGE THIS to re-route (CDM hop)<br>    "equipment_requested": { "...": "same as create" },<br>    "general_facilities": { "...": "same as create" },<br>    "function_rooms": { "purpose": "", "items": [] },<br>    "audiovisual_equipment": { "...": "same as create" }<br>  }<br>}</pre> |
| **GET** | `/api/v1/students/events/{event}/submissions/{submission}/notifications` | **Response `200`** — stepper / timeline. <pre>{<br>  "data": [<br>    {<br>      "submission_id": "s001",<br>      "sent_at": "2026-09-11T08:30:00Z",<br>      "signatory": "adv001",<br>      "notif_type": "denied",<br>      "comment": "Budget is incomplete."<br>    }<br>  ]<br>}</pre>`notif_type` is `approved`, `fully approved`, or `denied`. |
| **GET** | `/api/v1/students/events/{event}/submissions/{submission}/appeals` | **Response `200`** <pre>{<br>  "data": [<br>    {<br>      "submission_id": "s001",<br>      "appeal_id": "ap001",<br>      "event_id": "e001",<br>      "sent_at": "2026-09-12T10:00:00Z",<br>      "signatory_destination": "dean001",<br>      "comment": "Requesting review — budget was miscalculated.",<br>      "status": "open",<br>      "resolution": null,<br>      "resolved_at": null,<br>      "resolved_comment": null<br>    }<br>  ]<br>}</pre> |
| **POST** | `/api/v1/students/appeals` | **Request body** — submission must be `denied`. Routed to the org Dean. **Response `201`** is one appeal object. <pre>{<br>  "event_id": "e001",<br>  "submission_id": "s001",<br>  "comment": "Requesting review — budget was miscalculated in original denial."<br>}</pre> |
| **GET** | `/api/v1/students/deadlines` | **Response `200`** — upcoming deadlines (`deadline` ≥ now) for the org’s events (first 25 events). <pre>{<br>  "data": [<br>    {<br>      "event_id": "e001",<br>      "deadline_id": "d001",<br>      "sent_at": "2026-09-01T09:00:00Z",<br>      "deadline": "2026-09-20T23:59:59Z"<br>    }<br>  ]<br>}</pre> |

---

## Signatory routes

`X-Api-Key: <API_TOKEN_SIGNATORY>`

Scoped to the JWT desk (`custom:signatory_id`). Queue and appeals are that signatory only.

| Method | Endpoint | Example JSON |
| :---- | :---- | :---- |
| **GET** | `/api/v1/signatories/submissions` | **Response `200`** — items currently on this JWT signatory’s desk (GSI2). Same submission objects as the student list. <pre>{<br>  "data": [<br>    {<br>      "event_id": "e001",<br>      "submission_id": "s001",<br>      "status": "pending",<br>      "current_signatory": "adv001",<br>      "activity_details": { "title_and_nature": "Hack Night: Intro to Web Dev" }<br>    }<br>  ]<br>}</pre> |
| **GET** | `/api/v1/signatories/events/{event}/submissions/{submission}` | **Response `200`** — full SAAF, only if `current_signatory` is this JWT signatory. Same body as student GET one. **404** if it is not on their desk. |
| **POST** | `/api/v1/signatories/events/{event}/submissions/{submission}/approve` | **No request body.** **Response `200`** — extra-curricular + venue reservation advances Adviser → OSAAR (then CDM). Co-curricular inserts Dean after Adviser. Last step sets `status: "approved"` and leaves the queue. <pre>{<br>  "data": {<br>    "event_id": "e001",<br>    "submission_id": "s001",<br>    "status": "pending",<br>    "current_signatory": "osaar001"<br>  }<br>}</pre>After the last approval: `"status": "approved"` and `"current_signatory"` stays the last approver. |
| **POST** | `/api/v1/signatories/events/{event}/submissions/{submission}/deny` | **Request body** — `comment` is required. **Response `200`** drops the item from the queue (`status: "denied"`). <pre>{<br>  "comment": "Budget is incomplete. Recalculate grand_total and resubmit."<br>}</pre> |
| **GET** | `/api/v1/signatories/appeals` | **Response `200`** — open appeals routed to this signatory (usually Dean). <pre>{<br>  "data": [<br>    {<br>      "submission_id": "s001",<br>      "appeal_id": "ap001",<br>      "event_id": "e001",<br>      "sent_at": "2026-09-12T10:00:00Z",<br>      "signatory_destination": "dean001",<br>      "comment": "Please reconsider.",<br>      "status": "open",<br>      "resolution": null,<br>      "resolved_at": null,<br>      "resolved_comment": null<br>    }<br>  ]<br>}</pre> |
| **POST** | `/api/v1/signatories/events/{event}/submissions/{submission}/appeals/{appeal}/resolve` | **Request body.** `resolution` is `upheld` (stay denied) or `overturned` (reopen onto this signatory’s queue). **Response `200`**. <pre>{<br>  "resolution": "overturned",<br>  "comment": "Budget correction accepted. Please re-approve."<br>}</pre>**Response example:** <pre>{<br>  "data": {<br>    "appeal_id": "ap001",<br>    "status": "resolved",<br>    "resolution": "overturned",<br>    "resolved_at": "2026-09-12T11:00:00Z",<br>    "resolved_comment": "Budget correction accepted. Please re-approve."<br>  }<br>}</pre> |

---

## Admin routes

`X-Api-Key: <API_TOKEN_ADMIN>`

Admin list/show routes are global (not scoped by org). Stamp `organization_id` from `POST /organizations` onto Cognito students as `custom:organization_id`, and `signatory_id` from `POST /signatories` onto Cognito signatories as `custom:signatory_id`.

Signatories are people identified by `signatory_id`. Org desks (`ORGANIZATION.signatories`) are a separate list on the organization. Set them with `POST` / `PUT /organizations`. `POST` / `PUT /signatories` do **not** write that list. There is no delete route; replace a person **in place** with `PUT` on the same id. A new uuid does not move in-flight `GSI2` queues, open `GSI3` appeals, org desks, notification snapshots, or Cognito `custom:signatory_id`.

| Method | Endpoint | Example JSON |
| :---- | :---- | :---- |
| **GET** | `/api/v1/admins/submissions` | **Response `200`** — scan of all submissions. Optional query: `?status=pending` (`pending` \| `approved` \| `denied`) and `?activity_type=extra-curricular`. Same list item shape as student GET list. |
| **GET** | `/api/v1/admins/events/{event}/submissions/{submission}` | **Response `200`** — full SAAF plus timeline. <pre>{<br>  "data": {<br>    "event_id": "e001",<br>    "submission_id": "s001",<br>    "status": "denied",<br>    "current_signatory": "adv001",<br>    "activity_details": { "...": "full SAAF" },<br>    "notifications": [<br>      {<br>        "submission_id": "s001",<br>        "sent_at": "2026-09-11T08:30:00Z",<br>        "signatory": "adv001",<br>        "notif_type": "denied",<br>        "comment": "Fix the budget."<br>      }<br>    ],<br>    "appeals": [<br>      {<br>        "appeal_id": "ap001",<br>        "comment": "Please reconsider.",<br>        "status": "open"<br>      }<br>    ]<br>  }<br>}</pre> |
| **GET** | `/api/v1/admins/appeals` | **Response `200`** — open appeals system-wide. Same appeal objects as student/signatory lists. |
| **GET** | `/api/v1/admins/organizations` | **Response `200`**. `signatories` is the org desk list used to route new submissions and the next approve hop. Empty until you send `signatories` on `POST` or `PUT /organizations` (creating a signatory person does not fill it). <pre>{<br>  "data": [<br>    {<br>      "organization_id": "a1b2",<br>      "name": "Mapua Computing Society",<br>      "signatories": [<br>        { "role": "adviser", "signatory_id": "adv001" },<br>        { "role": "cdm", "signatory_id": "cdm001" },<br>        { "role": "dean", "signatory_id": "dean001" }<br>      ]<br>    }<br>  ]<br>}</pre> |
| **POST** | `/api/v1/admins/organizations` | **Request body.** Optional `signatories` is the desk list (`role` + existing `signatory_id`). Omit it and the org starts with `signatories: []`. Unknown signatory id → **422**. **Response `201`** includes a generated `organization_id` — put that value on each student Cognito user as `custom:organization_id`. <pre>{<br>  "name": "IEEE Mapua",<br>  "signatories": [<br>    { "role": "adviser", "signatory_id": "adv001" },<br>    { "role": "cdm", "signatory_id": "cdm001" }<br>  ]<br>}</pre>**Response:** <pre>{<br>  "data": {<br>    "organization_id": "3f2c1a90-....",<br>    "name": "IEEE Mapua",<br>    "signatories": [<br>      { "role": "adviser", "signatory_id": "adv001" },<br>      { "role": "cdm", "signatory_id": "cdm001" }<br>    ]<br>  }<br>}</pre> |
| **PUT** | `/api/v1/admins/organizations/{organization}` | **Request body** — full replace of `name` and `signatories` (send the list you want kept; `[]` clears desks). Path is the org id (`a1b2` or `ORGANIZATION#a1b2`). Unknown id → **404**. Unknown signatory id → **422**. Does **not** rewrite in-flight submissions. **Response `200`**. <pre>{<br>  "name": "IEEE Mapua",                    ← CHANGE THIS<br>  "signatories": [<br>    { "role": "adviser", "signatory_id": "adv001" },<br>    { "role": "dean", "signatory_id": "dean001" }  ← CHANGE THIS<br>  ]<br>}</pre> |
| **GET** | `/api/v1/admins/signatories` | **Response `200`**. People only — not scoped by org. `department` is set for deans (`ROLE#DEAN#{DEPARTMENT}`). `organization_id` is unused (`null`; not stored on the SIGNATORY item). <pre>{<br>  "data": [<br>    {<br>      "signatory_id": "adv001",<br>      "name": "Prof. Juan Dela Cruz",<br>      "role": "adviser",<br>      "department": null,<br>      "organization_id": null<br>    },<br>    {<br>      "signatory_id": "dean001",<br>      "name": "Dean Maria Santos",<br>      "role": "dean",<br>      "department": "SOIT",<br>      "organization_id": null<br>    }<br>  ]<br>}</pre>`role` is `adviser`, `cdm`, `dean`, `osaar`, or `admin`. This `admin` is a signatory desk role, not the Cognito `admin` group. |
| **POST** | `/api/v1/admins/signatories` | **Request body.** Creates a person (`PK`/`SK` `SIGNATORY#{uuid}`). `role` is `adviser`, `cdm`, `dean`, `osaar`, or `admin`. GSI4 is `ROLE#{ROLE}`, or `ROLE#DEAN#{DEPARTMENT}` when `role` is `dean` and `department` is sent (stored uppercase, e.g. `soit` → `SOIT`). `department` is ignored unless `role` is `dean`. Does **not** take `organization_id` and does **not** write `ORGANIZATION.signatories`. Approval routing uses adviser, optional dean (`co-curricular`), osaar, and optional CDM (venue reservation). `admin` is not in the sequence. **Response `201`**. Put `signatory_id` on the Cognito user as `custom:signatory_id`. <pre>{<br>  "name": "Prof. Juan Dela Cruz",<br>  "role": "adviser"<br>}</pre>OSAAR / admin: <pre>{<br>  "name": "OSAAR Officer",<br>  "role": "osaar"<br>}</pre> |
| **PUT** | `/api/v1/admins/signatories/{signatory}` | **Request body** — `name`, `role`, and optional `department` (dean only). The path `{signatory}` is the person id (`adv001` or `SIGNATORY#adv001`). Updates that SIGNATORY item and GSI4 in place, so existing `GSI2` / `GSI3` / org desk copies keep working. Unknown id → **404**. Does **not** take `organization_id`, does **not** write `ORGANIZATION.signatories`, and does **not** rewrite past `NOTIFICATION.signatory` snapshots. **Response `200`**. <pre>{<br>  "name": "Prof. Juan Dela Cruz",          ← CHANGE THIS if the person changed<br>  "role": "dean",                          ← CHANGE THIS (adviser \| cdm \| dean \| osaar \| admin)<br>  "department": "SOIT"                     ← CHANGE THIS for deans only<br>}</pre> |

---

## SAAF create body

Use this as the **POST** `/api/v1/students/submissions` body. For **PUT**, copy it, drop `event_id`, and edit the `← CHANGE THIS` fields listed in the PUT row.

```json
{
  "event_id": "e001",
  "submission_type": "saaf",
  "activity_classification": {
    "activity_type": "extra-curricular",
    "total_org_members": 42
  },
  "proponents": [
    {
      "id": "p001",
      "position_title": "President",
      "first_name": "Nicole",
      "middle_name": "R",
      "last_name": "Santos",
      "suffix": "",
      "student_number": "2021-00123",
      "program_and_year": "BSCS-3",
      "date_of_submission": "2026-09-10",
      "department": "CCIS",
      "position_of_applicant": "President",
      "org_or_course_section": "Mapua Computing Society",
      "contact_number": "09171234567",
      "email_address": "nsantos@mymail.mapua.edu.ph",
      "facebook_link": "fb.com/nicole.santos"
    }
  ],
  "activity_details": {
    "title_and_nature": "Hack Night: Intro to Web Dev",
    "description": "A beginner-friendly hackathon night.",
    "objectives": "Introduce first-year students to web development.",
    "venue": "MPH 2nd Floor",
    "date_of_event": "2026-10-05",
    "day_of_event": "Monday",
    "time_of_event": "17:00",
    "expected_participants": 60,
    "individual_contribution": 0,
    "proposed_budget": 5000
  },
  "institutional_alignment": {
    "mission_statements": {
      "competitive": true,
      "research": false,
      "solutions": true
    },
    "core_values_explanation": "Promotes collaboration and innovation.",
    "peo_explanation": "Builds technical competency outside curriculum.",
    "sdg_explanation": "Supports SDG 4: Quality Education."
  },
  "detailed_budget_proposal": {
    "items": [
      {
        "item_no": "1",
        "unit": 1,
        "quantity": 60,
        "price_per_unit": 50,
        "total": 3000
      }
    ],
    "grand_total": 3000
  },
  "venue_reservation": {
    "has_reservation": true,
    "equipment_requested": {
      "monoblock_chairs": true,
      "whiteboards": false,
      "tables": true,
      "rostrum": false,
      "flags_with_stand": false,
      "panel_boards": false,
      "others_specified": ""
    },
    "general_facilities": {
      "purpose": "Hackathon venue",
      "items": [
        {
          "item": "MPH",
          "date_of_use": "2026-10-05",
          "time_of_use": "17:00",
          "location": "MPH 2F"
        }
      ]
    },
    "function_rooms": {
      "purpose": "",
      "items": []
    },
    "audiovisual_equipment": {
      "purpose": "Projector for demo",
      "items": [
        {
          "date_needed": "2026-10-05",
          "time_needed": "17:00",
          "equipment_needed": "Projector",
          "remarks": "1 unit"
        }
      ]
    }
  }
}
```
