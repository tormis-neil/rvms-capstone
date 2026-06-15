/**
 * RVMS — Prototype Feedback Form generator (Google Apps Script)
 * ------------------------------------------------------------------
 * Creates ONE combined Google Form (Driver Mobile App + Admin Website) with:
 *   Section 1  About You (role, agency, tech use, platform tested)
 *   Section 2  Does it meet your needs?  (interview findings + the 4 objectives)
 *   Section 3  Ease of Use  (System Usability Scale — the standard 10 items)
 *   Section 4  Experience, Design & Acceptance  (TAM usefulness/intent, CSAT, NPS)
 *   Section 5  Suggestions & Comments  (open-ended)
 *
 * Framework: ISO/IEC 25010 (needs fit) + System Usability Scale (SUS) +
 * Technology Acceptance Model (TAM) + open-ended.
 *
 * HOW TO RUN — see feedback/README.md (paste into script.google.com and Run).
 * Anonymous: no email collected, no sign-in required.
 */
function createRvmsFeedbackForm() {
  var AGREE = ['Strongly Disagree', 'Disagree', 'Neutral', 'Agree', 'Strongly Agree'];

  var form = FormApp.create('RVMS Prototype Feedback — Driver App & Admin Website');
  form.setDescription(
    'Thank you for testing the Rescue Vehicle Management System (RVMS) prototype.\n\n' +
    'Your honest feedback helps us improve it before development. This survey is anonymous and takes about ' +
    '5–8 minutes. The prototype uses sample data, so please rate your overall impression of the screens and ' +
    'flows you tried.'
  );
  form.setProgressBar(true);
  form.setCollectEmail(false);
  form.setAllowResponseEdits(false);
  form.setShowLinkToRespondAgain(false);
  form.setConfirmationMessage('Salamat! Your feedback has been recorded. Thank you for helping improve the RVMS.');
  try { form.setRequireLogin(false); } catch (e) { /* personal Gmail accounts: safe to ignore */ }

  // ---------------- Section 1: About You ----------------
  form.addSectionHeaderItem()
    .setTitle('Section 1 — About You')
    .setHelpText('Your answers are anonymous. We only ask these to understand the feedback.');

  form.addMultipleChoiceItem().setTitle('Your role')
    .setChoiceValues(['Authorized Driver', 'Agency Administrator', 'Other']).setRequired(true);

  form.addMultipleChoiceItem().setTitle('Your agency')
    .setChoiceValues([
      'BFP — Bureau of Fire Protection',
      'PNP — Philippine National Police',
      'CDRRMO — City Disaster Risk Reduction and Management Office',
      'CHO — City Health Office'
    ]).setRequired(true);

  form.addMultipleChoiceItem().setTitle('How often do you use a smartphone or computer for work?')
    .setChoiceValues(['Rarely or never', 'A few times a month', 'A few times a week', 'Daily']).setRequired(true);

  form.addCheckboxItem().setTitle('Which part(s) of the prototype did you test?')
    .setChoiceValues(['Driver Mobile App', 'Admin Website']).setRequired(true);

  // ---------------- Section 2: Does it meet your needs? ----------------
  form.addPageBreakItem().setTitle('Section 2 — Does it meet your needs?')
    .setHelpText('Based on what your agency shared during the interviews. Choose how much you agree with each ' +
                 'statement. Please answer for the part(s) you tested.');

  form.addGridItem().setTitle('How much do you agree with each statement?')
    .setRows([
      'This system would help replace our paper-based forms and logbooks.',
      'It brings scattered vehicle, driver, and maintenance information into one place.',
      'It would help reduce delays in reporting damage and updating records.',
      'It gives a clear, at-a-glance view of which vehicles are ready, deployed, or under maintenance.',
      'The damage reports it captures are detailed enough for assessment.',
      'Recording vehicle and driver information is clear and complete.',                                  // Obj 1
      'The inspection, damage, repair, and preventive-maintenance features fit how we track maintenance.', // Obj 2
      'The dispatch and vehicle-status features fit how we deploy and monitor vehicles.',                  // Obj 3
      'The reports it generates are useful and relevant to our work.',                                     // Obj 4
      'Overall, the system addresses the needs we described during the interviews.'
    ])
    .setColumns(AGREE).setRequired(true);

  // ---------------- Section 3: Ease of Use (System Usability Scale) ----------------
  form.addPageBreakItem().setTitle('Section 3 — Ease of Use')
    .setHelpText('A standard set of usability statements. Some are positive and some are negative — please read ' +
                 'each one carefully before answering.');

  form.addGridItem().setTitle('How much do you agree with each statement?')
    .setRows([
      'I think that I would like to use this system frequently.',                                       // SUS 1 (+)
      'I found the system unnecessarily complex.',                                                      // SUS 2 (-)
      'I thought the system was easy to use.',                                                          // SUS 3 (+)
      'I think that I would need the support of a technical person to be able to use this system.',     // SUS 4 (-)
      'I found the various functions in this system were well integrated.',                             // SUS 5 (+)
      'I thought there was too much inconsistency in this system.',                                     // SUS 6 (-)
      'I would imagine that most people would learn to use this system very quickly.',                  // SUS 7 (+)
      'I found the system very cumbersome to use.',                                                     // SUS 8 (-)
      'I felt very confident using the system.',                                                        // SUS 9 (+)
      'I needed to learn a lot of things before I could get going with this system.'                    // SUS 10 (-)
    ])
    .setColumns(AGREE).setRequired(true);

  // ---------------- Section 4: Experience, Design & Acceptance ----------------
  form.addPageBreakItem().setTitle('Section 4 — Experience, Design & Acceptance');

  form.addGridItem().setTitle('How much do you agree with each statement?')
    .setRows([
      'The colours and status labels (e.g., Operational, Under Preventive Maintenance) are clear and easy to understand.',
      'The design looks professional and appropriate for a government agency.',
      'The mobile app and the website feel consistent with each other.',
      'The text and buttons are large and readable enough for use in the field.',
      'Using this system would improve how we manage rescue vehicles.',          // TAM — perceived usefulness
      'If it were deployed, I would use this system in our daily operations.'     // TAM — intention to use
    ])
    .setColumns(AGREE).setRequired(true);

  form.addScaleItem().setTitle('Overall, how satisfied are you with the prototype?')
    .setBounds(1, 5).setLabels('Very dissatisfied', 'Very satisfied').setRequired(true);            // CSAT

  form.addScaleItem().setTitle('How likely are you to recommend this system to a colleague?')
    .setBounds(0, 10).setLabels('Not at all likely', 'Extremely likely').setRequired(false);       // NPS

  // ---------------- Section 5: Suggestions & Comments ----------------
  form.addPageBreakItem().setTitle('Section 5 — Suggestions & Comments');
  form.addParagraphTextItem().setTitle('What did you like most about the prototype?');
  form.addParagraphTextItem().setTitle('Was anything confusing or difficult? Please describe.');
  form.addParagraphTextItem().setTitle('What features are missing or should be improved?');
  form.addParagraphTextItem().setTitle('Any other comments or suggestions?');

  // ---------------- Optional: auto-create a linked responses spreadsheet ----------------
  try {
    var ss = SpreadsheetApp.create('RVMS Prototype Feedback — Responses');
    form.setDestination(FormApp.DestinationType.SPREADSHEET, ss.getId());
  } catch (e) { /* if this fails, link a sheet later from the form's Responses tab */ }

  // ---------------- Output the links ----------------
  Logger.log('FORM CREATED ✓');
  Logger.log('Edit (build) URL  : ' + form.getEditUrl());
  Logger.log('Share (fill) URL  : ' + form.getPublishedUrl());
}
