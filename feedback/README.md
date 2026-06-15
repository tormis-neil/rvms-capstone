# RVMS Prototype Feedback Form

A ready-to-run **Google Apps Script** that builds the prototype feedback Google Form for you —
no manual question typing. One combined form covers both the **Driver Mobile App** and the
**Admin Website**.

## What the form measures

Anchored on recognized, citable instruments so the results stand up in Chapter 4:

| Section | What it covers | Framework |
|---|---|---|
| 1. About You | Role, agency, tech familiarity, platform tested | Respondent profile |
| 2. Does it meet your needs? | The interview-identified problems + the 4 study objectives | ISO/IEC 25010 (functional suitability & satisfaction) |
| 3. Ease of Use | The standard 10 usability statements | **System Usability Scale (SUS)** → 0–100 score |
| 4. Experience, Design & Acceptance | Clarity, professionalism, consistency, usefulness, intent to use, overall satisfaction, recommend | **TAM** + CSAT + NPS |
| 5. Suggestions & Comments | Open-ended feedback | Qualitative |

The survey is **anonymous** (no email collected, no sign-in required) and takes ~5–8 minutes.

## How to create the form (one time, ~2 minutes)

1. Go to **https://script.google.com** and sign in with the Google account that should own the form.
2. Click **New project**.
3. Delete the default `function myFunction() {}` and **paste the entire contents** of
   [`create-rvms-feedback-form.gs`](create-rvms-feedback-form.gs).
4. Click **Save** (💾).
5. In the toolbar, make sure the function **`createRvmsFeedbackForm`** is selected, then click **Run** (▶).
6. The first run asks for permission — click **Review permissions** → choose your account → **Allow**.
   (You may see an "unverified app" notice for your own script; choose **Advanced → Go to project → Allow**.)
7. Open **Execution log** (View → Logs, or the panel that appears). It prints two links:
   - **Edit (build) URL** — open this to review/tweak the form.
   - **Share (fill) URL** — send this to your testers.

A **"RVMS Prototype Feedback — Responses"** spreadsheet is also created in your Drive and linked to
the form automatically. (If that step is skipped, open the form → **Responses** tab → **Link to Sheets**.)

## Scoring the SUS (Section 3) — for your manuscript

The 10 Section 3 items make up the System Usability Scale. Convert each answer to a number
(Strongly Disagree = 1 … Strongly Agree = 5), then **per respondent**:

1. **Odd-numbered** items (1, 3, 5, 7, 9): `score = answer − 1`
2. **Even-numbered** items (2, 4, 6, 8, 10): `score = 5 − answer`
3. Add the 10 adjusted scores (range 0–40), then **multiply by 2.5** → an SUS score from **0 to 100**.
4. Report the **average** SUS across respondents.

Interpretation guide: **~68 = average**, **80+ = excellent**, **below ~50 = poor** (needs work).

> The other Likert sections (2 and 4) are reported as **mean per item** (1–5) and as section averages.
> CSAT is the mean of the 1–5 satisfaction item; NPS = %Promoters (9–10) − %Detractors (0–6).

## Notes

- Works with a personal Gmail or a Workspace account. On Workspace, the script tries to allow
  responses without sign-in; if your domain blocks that, change it in the form's **Settings**.
- Nothing here touches the prototype — it only creates a Google Form in your own Drive.
- Want changes (shorter "lite" version, Tagalog translations, remove NPS, etc.)? Edit the
  `.gs` rows or ask and I'll adjust it.
