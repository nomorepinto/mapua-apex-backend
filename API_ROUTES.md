# API routes

All v1 routes sit under `/api/v1`. Collections and single resources are wrapped in Laravel's `data` key.

Headers (every v1 route):

```http
X-Api-Key: <API_TOKEN_STUDENT | API_TOKEN_SIGNATORY | API_TOKEN_ADMIN>
Authorization: Bearer <Cognito JWT>
```

- Student JWT needs `custom:organization_id`.
- Signatory JWT needs `custom:signatory_id`.
- Admin JWT does not need an org claim.

Wrong or missing key/JWT → `401`. Over limit → `429`.

In **PUT** examples, lines marked `← CHANGE THIS` are the fields you typically edit. Send the full body anyway; the API replaces the SAAF document, not a patch of one key.

---

## Student routes

`X-Api-Key: <API_TOKEN_STUDENT>`

| Method | Endpoint | Example JSON |
| :---- | :---- | :---- |
| **GET** | `/api/v1/students/submissions` | **Response `200`** — list for the JWT org. <pre>{<br>  "data": [<br>    {<br>      "event_id": "e001",<br>      "submission_id": "s001",<br>      "submission_type": "saaf",<br>      "sent_at": "2026-09-10T14:00:00Z",<br>      "status": "pending",<br>      "current_signatory": "adv001",<br>      "activity_classification": {<br>        "activity_type": "extra-curricular",<br>        "total_org_members": 42<br>      },<br>      "proponents": [ { "id": "p001", "first_name": "Nicole", "last_name": "Santos", "email_address": "nsantos@mymail.mapua.edu.ph" } ],<br>      "activity_details": {<br>        "title_and_nature": "Hack Night: Intro to Web Dev",<br>        "venue": "MPH 2nd Floor",<br>        "date_of_event": "2026-10-05"<br>      },<br>      "institutional_alignment": { "...": "..." },<br>      "detailed_budget_proposal": { "grand_total": 3000 },<br>      "venue_reservation": { "has_reservation": true }<br>    }<br>  ]<br>}</pre> |
| **GET** | `/api/v1/students/events/{event}/submissions/{submission}` | **Response `200`** — one full SAAF. Path uses both ids because PK is `EVENT#` + `SUBMISSION#`. <pre>{<br>  "data": {<br>    "event_id": "e001",<br>    "submission_id": "s001",<br>    "submission_type": "saaf",<br>    "sent_at": "2026-09-10T14:00:00Z",<br>    "status": "pending",<br>    "current_signatory": "adv001",<br>    "activity_classification": {<br>      "activity_type": "extra-curricular",<br>      "total_org_members": 42<br>    },<br>    "proponents": [ { "id": "p001", "position_title": "President", "first_name": "Nicole", "middle_name": "R", "last_name": "Santos", "suffix": "", "student_number": "2021-00123", "program_and_year": "BSCS-3", "date_of_submission": "2026-09-10", "department": "CCIS", "position_of_applicant": "President", "org_or_course_section": "Mapua Computing Society", "contact_number": "09171234567", "email_address": "nsantos@mymail.mapua.edu.ph", "facebook_link": "fb.com/nicole.santos" } ],<br>    "activity_details": {<br>      "title_and_nature": "Hack Night: Intro to Web Dev",<br>      "description": "A beginner-friendly hackathon night.",<br>      "objectives": "Introduce first-year students to web development.",<br>      "venue": "MPH 2nd Floor",<br>      "date_of_event": "2026-10-05",<br>      "day_of_event": "Monday",<br>      "time_of_event": "17:00",<br>      "expected_participants": 60,<br>      "individual_contribution": 0,<br>      "proposed_budget": 5000<br>    },<br>    "institutional_alignment": {<br>      "mission_statements": { "competitive": true, "research": false, "solutions": true },<br>      "core_values_explanation": "Promotes collaboration and innovation.",<br>      "peo_explanation": "Builds technical competency outside curriculum.",<br>      "sdg_explanation": "Supports SDG 4: Quality Education."<br>    },<br>    "detailed_budget_proposal": {<br>      "items": [ { "item_no": "1", "unit": 1, "quantity": 60, "price_per_unit": 50, "total": 3000 } ],<br>      "grand_total": 3000<br>    },<br>    "venue_reservation": {<br>      "has_reservation": true,<br>      "equipment_requested": { "monoblock_chairs": true, "whiteboards": false, "tables": true, "rostrum": false, "flags_with_stand": false, "panel_boards": false, "others_specified": "" },<br>      "general_facilities": { "purpose": "Hackathon venue", "items": [ { "item": "MPH", "date_of_use": "2026-10-05", "time_of_use": "17:00", "location": "MPH 2F" } ] },<br>      "function_rooms": { "purpose": "", "items": [] },<br>      "audiovisual_equipment": { "purpose": "Projector for demo", "items": [ { "date_needed": "2026-10-05", "time_needed": "17:00", "equipment_needed": "Projector", "remarks": "1 unit" } ] }<br>    }<br>  }<br>}</pre> |
| **POST** | `/api/v1/students/submissions` | **Request body** — if `event_id` does not exist yet, the API creates that event under the JWT org. **Response `201`** is the same shape as GET one (ids filled in). Full required body is in [SAAF create body](#saaf-create-body) below. Compact: <pre>{<br>  "event_id": "e001",<br>  "submission_type": "saaf",<br>  "activity_classification": {<br>    "activity_type": "extra-curricular",<br>    "total_org_members": 42<br>  },<br>  "proponents": [ { "...": "see SAAF create body" } ],<br>  "activity_details": { "...": "see SAAF create body" },<br>  "institutional_alignment": { "...": "see SAAF create body" },<br>  "detailed_budget_proposal": { "...": "see SAAF create body" },<br>  "venue_reservation": { "has_reservation": true, "...": "see SAAF create body" }<br>}</pre> |
| **PUT** | `/api/v1/students/events/{event}/submissions/{submission}` | **Request body** — same SAAF fields as POST **except omit `event_id`** (it is in the URL). Allowed when `status` is `pending` or `denied`, not `approved`. Routing re-runs if `activity_type` or `has_reservation` change. **Response `200`** is the updated GET-one object (`status` back to `pending`, `current_signatory` reset to Adviser). Highlight typical resubmit edits: <pre>{<br>  "submission_type": "saaf",<br>  "activity_classification": {<br>    "activity_type": "extra-curricular",   ← CHANGE THIS to re-route (Adviser only vs Adviser→CDM)<br>    "total_org_members": 42<br>  },<br>  "proponents": [ { "...": "same as create" } ],<br>  "activity_details": {<br>    "title_and_nature": "Hack Night: Intro to Web Dev (revised)",  ← CHANGE THIS<br>    "description": "Updated description after denial.",            ← CHANGE THIS<br>    "objectives": "Introduce first-year students to web development.",<br>    "venue": "MPH 2nd Floor",<br>    "date_of_event": "2026-10-12",                                 ← CHANGE THIS<br>    "day_of_event": "Monday",<br>    "time_of_event": "17:00",<br>    "expected_participants": 60,<br>    "individual_contribution": 0,<br>    "proposed_budget": 4500                                        ← CHANGE THIS<br>  },<br>  "institutional_alignment": { "...": "same as create" },<br>  "detailed_budget_proposal": {<br>    "items": [ { "item_no": "1", "unit": 1, "quantity": 60, "price_per_unit": 40, "total": 2400 } ],  ← CHANGE THIS<br>    "grand_total": 2400                                              ← CHANGE THIS<br>  },<br>  "venue_reservation": {<br>    "has_reservation": true,                                         ← CHANGE THIS to re-route<br>    "equipment_requested": { "...": "same as create" },<br>    "general_facilities": { "...": "same as create" },<br>    "function_rooms": { "purpose": "", "items": [] },<br>    "audiovisual_equipment": { "...": "same as create" }<br>  }<br>}</pre> |
| **GET** | `/api/v1/students/events/{event}/submissions/{submission}/notifications` | **Response `200`** — stepper / timeline. <pre>{<br>  "data": [<br>    {<br>      "submission_id": "s001",<br>      "sent_at": "2026-09-11T08:30:00Z",<br>      "signatory": "adv001",<br>      "notif_type": "denied",<br>      "comment": "Budget is incomplete."<br>    }<br>  ]<br>}</pre>`notif_type` is `approved`, `fully approved`, or `denied`. |
| **GET** | `/api/v1/students/events/{event}/submissions/{submission}/appeals` | **Response `200`** <pre>{<br>  "data": [<br>    {<br>      "submission_id": "s001",<br>      "appeal_id": "ap001",<br>      "event_id": "e001",<br>      "sent_at": "2026-09-12T10:00:00Z",<br>      "signatory_destination": "dean001",<br>      "comment": "Requesting review — budget was miscalculated.",<br>      "status": "open",<br>      "resolution": null,<br>      "resolved_at": null,<br>      "resolved_comment": null<br>    }<br>  ]<br>}</pre> |
| **POST** | `/api/v1/students/appeals` | **Request body** — submission must be `denied`. Routed to the org Dean. **Response `201`** is one appeal object. <pre>{<br>  "event_id": "e001",<br>  "submission_id": "s001",<br>  "comment": "Requesting review — budget was miscalculated in original denial."<br>}</pre> |
| **GET** | `/api/v1/students/deadlines` | **Response `200`** — upcoming deadlines (`deadline` ≥ now) for the org’s events (first 25 events). <pre>{<br>  "data": [<br>    {<br>      "event_id": "e001",<br>      "deadline_id": "d001",<br>      "sent_at": "2026-09-01T09:00:00Z",<br>      "deadline": "2026-09-20T23:59:59Z"<br>    }<br>  ]<br>}</pre> |

---

## Signatory routes

`X-Api-Key: <API_TOKEN_SIGNATORY>`

| Method | Endpoint | Example JSON |
| :---- | :---- | :---- |
| **GET** | `/api/v1/signatories/submissions` | **Response `200`** — items currently on this signatory’s desk (GSI2). Same submission objects as the student list. <pre>{<br>  "data": [<br>    {<br>      "event_id": "e001",<br>      "submission_id": "s001",<br>      "status": "pending",<br>      "current_signatory": "adv001",<br>      "activity_details": { "title_and_nature": "Hack Night: Intro to Web Dev" }<br>    }<br>  ]<br>}</pre> |
| **GET** | `/api/v1/signatories/events/{event}/submissions/{submission}` | **Response `200`** — full SAAF, only if `current_signatory` is this JWT signatory. Same body as student GET one. **404** if it is not on their desk. |
| **POST** | `/api/v1/signatories/events/{event}/submissions/{submission}/approve` | **No request body.** **Response `200`** — extra-curricular + venue reservation advances Adviser → CDM; last step sets `status: "approved"` and leaves the queue. <pre>{<br>  "data": {<br>    "event_id": "e001",<br>    "submission_id": "s001",<br>    "status": "pending",<br>    "current_signatory": "cdm001"<br>  }<br>}</pre>After the last approval: `"status": "approved"` and `"current_signatory"` stays the last approver. |
| **POST** | `/api/v1/signatories/events/{event}/submissions/{submission}/deny` | **Request body** — `comment` is required. **Response `200`** drops the item from the queue (`status: "denied"`). <pre>{<br>  "comment": "Budget is incomplete. Recalculate grand_total and resubmit."<br>}</pre> |
| **GET** | `/api/v1/signatories/appeals` | **Response `200`** — open appeals routed to this signatory (usually Dean). <pre>{<br>  "data": [<br>    {<br>      "submission_id": "s001",<br>      "appeal_id": "ap001",<br>      "event_id": "e001",<br>      "sent_at": "2026-09-12T10:00:00Z",<br>      "signatory_destination": "dean001",<br>      "comment": "Please reconsider.",<br>      "status": "open",<br>      "resolution": null,<br>      "resolved_at": null,<br>      "resolved_comment": null<br>    }<br>  ]<br>}</pre> |
| **POST** | `/api/v1/signatories/events/{event}/submissions/{submission}/appeals/{appeal}/resolve` | **Request body.** `resolution` is `upheld` (stay denied) or `overturned` (reopen onto this signatory’s queue). **Response `200`**. <pre>{<br>  "resolution": "overturned",<br>  "comment": "Budget correction accepted. Please re-approve."<br>}</pre>**Response example:** <pre>{<br>  "data": {<br>    "appeal_id": "ap001",<br>    "status": "resolved",<br>    "resolution": "overturned",<br>    "resolved_at": "2026-09-12T11:00:00Z",<br>    "resolved_comment": "Budget correction accepted. Please re-approve."<br>  }<br>}</pre> |

---

## Admin routes

`X-Api-Key: <API_TOKEN_ADMIN>`

| Method | Endpoint | Example JSON |
| :---- | :---- | :---- |
| **GET** | `/api/v1/admins/submissions` | **Response `200`** — scan of all submissions. Optional query: `?status=pending` (`pending` \| `approved` \| `denied`) and `?activity_type=extra-curricular`. Same list item shape as student GET list. |
| **GET** | `/api/v1/admins/events/{event}/submissions/{submission}` | **Response `200`** — full SAAF plus timeline. <pre>{<br>  "data": {<br>    "event_id": "e001",<br>    "submission_id": "s001",<br>    "status": "denied",<br>    "current_signatory": "adv001",<br>    "activity_details": { "...": "full SAAF" },<br>    "notifications": [<br>      {<br>        "submission_id": "s001",<br>        "sent_at": "2026-09-11T08:30:00Z",<br>        "signatory": "adv001",<br>        "notif_type": "denied",<br>        "comment": "Fix the budget."<br>      }<br>    ],<br>    "appeals": [<br>      {<br>        "appeal_id": "ap001",<br>        "comment": "Please reconsider.",<br>        "status": "open"<br>      }<br>    ]<br>  }<br>}</pre> |
| **GET** | `/api/v1/admins/appeals` | **Response `200`** — open appeals system-wide. Same appeal objects as student/signatory lists. |
| **GET** | `/api/v1/admins/organizations` | **Response `200`** <pre>{<br>  "data": [<br>    {<br>      "organization_id": "a1b2",<br>      "name": "Mapua Computing Society"<br>    }<br>  ]<br>}</pre> |
| **POST** | `/api/v1/admins/organizations` | **Request body.** **Response `201`** includes a generated `organization_id`. <pre>{<br>  "name": "IEEE Mapua"<br>}</pre>**Response:** <pre>{<br>  "data": {<br>    "organization_id": "3f2c1a90-....",<br>    "name": "IEEE Mapua"<br>  }<br>}</pre> |
| **GET** | `/api/v1/admins/signatories` | **Response `200`** <pre>{<br>  "data": [<br>    {<br>      "signatory_id": "adv001",<br>      "name": "Prof. Juan Dela Cruz",<br>      "role": "adviser",<br>      "organization_id": "a1b2"<br>    }<br>  ]<br>}</pre>`role` is `adviser`, `cdm`, or `dean`. |
| **POST** | `/api/v1/admins/signatories` | **Request body.** Writes GSI4 `ROLE#{ROLE}#ORG#{org}`. **Response `201`**. <pre>{<br>  "name": "Prof. Juan Dela Cruz",<br>  "role": "adviser",<br>  "organization_id": "a1b2"<br>}</pre> |
| **PUT** | `/api/v1/admins/signatories/{signatory}` | **Request body** — send all three fields. Reassignment does **not** rewrite past `NOTIFICATION.signatory` snapshots. **Response `200`**. Highlight what you change on an Adviser → Dean handoff: <pre>{<br>  "name": "Prof. Juan Dela Cruz",          ← CHANGE THIS if the person changed<br>  "role": "dean",                          ← CHANGE THIS (adviser \| cdm \| dean)<br>  "organization_id": "a1b2"                ← CHANGE THIS if they moved orgs<br>}</pre> |

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
