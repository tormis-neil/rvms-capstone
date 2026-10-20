# RVMS TASK PROGRESS: MANUSCRIPT & FINAL DEFENSE PITCH

**Date:** October 20, 2026  
**Project:** Rescue Vehicle Management System (RVMS) — Capstone Project  
**Institution:** Northwest Samar State University (NwSSU) — College of Computing and Information Sciences (CCIS)  
**Corpus / Repository Root:** `c:\rvms capstone\`  
**Primary Reference Files:**
- Verified Manuscript Source of Truth: [`skills/rvms-source-of-truth.md`](file:///c:/rvms%20capstone/skills/rvms-source-of-truth.md)
- Defense Presentation Pitch Document: [`RVMS_Final_Defense_Pitch.docx`](file:///c:/rvms%20capstone/RVMS_Final_Defense_Pitch.docx) & [`RVMS_Final_Defense_Pitch_Updated.docx`](file:///c:/rvms%20capstone/RVMS_Final_Defense_Pitch_Updated.docx)
- Editable Evaluation Table 17 Document: [`Table_17_System_Evaluation_Results.docx`](file:///c:/rvms%20capstone/Table_17_System_Evaluation_Results.docx) & [`Table_17_System_Evaluation_Results_Updated.docx`](file:///c:/rvms%20capstone/Table_17_System_Evaluation_Results_Updated.docx)
- Historical Seeding Rules: [`skills/historical-data-seeding-rules.md`](file:///c:/rvms%20capstone/skills/historical-data-seeding-rules.md)
- Institutional Benchmark Manuscripts: [`CalbayogGO-1.docx`](file:///c:/rvms%20capstone/CalbayogGO-1.docx) & [`FINAL-MANUSCRIPT-PathFinder-Mobile-Based-3D-navigation.pdf`](file:///c:/rvms%20capstone/FINAL-MANUSCRIPT-PathFinder-Mobile-Based-3D-navigation.pdf)

---

## 1. Executive Summary & Session State

This document serves as the persistent session memory for the RVMS capstone completion. In this session, we accomplished four major milestones:
1. **Chapter 4 Implementation Results Complete:** Established the System-to-Objective mapping modeled directly after accepted NwSSU capstone standards (CalbayogGO and PathFinder). Selected **Option B** (individual sequential figures per screen) and drafted/appended comprehensive narratives for all four specific objectives into `skills/rvms-source-of-truth.md`.
2. **ISO/IEC 25010 System Evaluation Added:** Documented and integrated client feedback results ($N = 8$ respondents from CHO, CDRRMO, BFP, and PNP across 7 official operational and administrative positions) as a formal evaluation section with Table 16 (Rating Scale) and Table 17 (Evaluation Results Matrix, Grand Mean = 4.73 "Strongly Agree") directly before Chapter 5.
3. **Diagram Standardizations & Image Enhancements:** Standardized figure numbering across DFDs (`Figure 4.1` & `Figure 4.2`) and ERDs (`Figure 6.1` & `Figure 6.2`). Enhanced all technical diagrams in `skills/diagrams/` to 300 DPI print quality using Unsharp Masking, contrast leveling, and clean `#FFFFFF` background normalization.
4. **Presentation Pitch & Script Integration:** Updated `RVMS_Final_Defense_Pitch.docx` with high-resolution image embeds and dual-audience defense scripts for Slide 5 (System Architecture) and Slide 6 (Context Diagram).

---

## 2. Chapter 4: Implementation Results Mapping (Option B)

All four specific objectives have been written in full academic detail and appended to [`skills/rvms-source-of-truth.md`](file:///c:/rvms%20capstone/skills/rvms-source-of-truth.md).

### Summary Table of Implementation Figures:

| Specific Objective | Figure Number | Screen / Interface Focus | Target Image Path |
|---|---|---|---|
| **Objective 1:**<br>*To input vehicle and driver information* | **Figure 7.1** | Add Driver Input Form (Web Dashboard modal) | `skills/screenshots/figure-7-1-add-driver-form.png` |
| | **Figure 7.2** | Add Vehicle Input Form (Presets, "Other" custom type, driver assignment) | `skills/screenshots/figure-7-2-add-vehicle-form.png` |
| | **Figure 7.3** | Driver Sign In and Self-Registration Screen (Mobile App) | `skills/screenshots/figure-7-3-driver-registration-mobile.png` |
| **Objective 2:**<br>*To track vehicle maintenance* | **Figure 8.1** | Submitted Daily BLOWBAGETS Inspection Details with Flagged Item (Mobile App) | `skills/screenshots/figure-8-1-submitted-inspection-flagged.png` |
| | **Figure 8.2** | Filled Damage & Defect Report Form Prior to Submission (Mobile App) | `skills/screenshots/figure-8-2-new-damage-report-filled.png` |
| | **Figure 8.3** | Primary Inspections & Damage Reports Management Table (Web Dashboard) | `skills/screenshots/figure-8-3-admin-inspections-damage-landing.png` |
| | **Figure 8.4** | Active Preventive Maintenance (PMS) Schedules Management Interface (Web) | `skills/screenshots/figure-8-4-active-pm-schedules.png` |
| | **Figure 8.5** | Historical Corrective Repair Logs Ledger (Web Dashboard) | `skills/screenshots/figure-8-5-all-repair-logs-table.png` |
| **Objective 3:**<br>*To provide an admin module for dispatch and vehicle management* | **Figure 9.1** | Vehicle Management and Fleet Inventory Interface (`/vehicles` data table) | `skills/screenshots/figure-9-1-vehicle-management-table.png` |
| | **Figure 9.2** | Comprehensive Dispatch Logs Ledger (`/dispatches` table: active & closed runs) | `skills/screenshots/figure-9-2-all-dispatch-logs-table.png` |
| **Objective 4:**<br>*To generate relevant reports* | **Figure 10.1** | Centralized Report Generation and Filter Configuration Interface (`/reports`) | `skills/screenshots/figure-10-1-report-generation-configuration.png` |
| | **Figure 10.2** | Generated Inspection Records Report Output (Tabular audit sheet with sign-offs) | `skills/screenshots/figure-10-2-inspection-records-report-output.png` |

---

## 3. Chapter 5: Relevant Source Code Selections

The panelists requested the core source code listings inside Chapter 5 (prior to or alongside Recommendations). Following the ultra-concise style of CalbayogGO and PathFinder, we finalized **one core file per objective** using simple 2-word headers:

1. **A. Vehicle Registration**  
   - **File:** [`backend/app/Http/Requests/StoreVehicleRequest.php`](file:///c:/rvms%20capstone/backend/app/Http/Requests/StoreVehicleRequest.php)  
   - **Caption:** `Listing 1. Vehicle Registration Validation Rules`  
   - **Brief:** *Validates vehicle specifications, unique plate numbers, custom vehicle types, and agency driver assignments.*
2. **B. Daily Inspection Submission**  
   - **File:** [`backend/app/Http/Controllers/Api/V1/InspectionController.php`](file:///c:/rvms%20capstone/backend/app/Http/Controllers/Api/V1/InspectionController.php)  
   - **Caption:** `Listing 2. Daily BLOWBAGETS Inspection and Alert Processing`  
   - **Brief:** *Processes driver pre-trip checklists, enforces defect remarks, and triggers administrative defect alerts.*
3. **C. Dispatch Management**  
   - **File:** [`backend/app/Http/Controllers/Web/DispatchController.php`](file:///c:/rvms%20capstone/backend/app/Http/Controllers/Web/DispatchController.php)  
   - **Caption:** `Listing 3. Vehicle Dispatch and Return Handling`  
   - **Brief:** *Manages mission departures, sets the dispatched vehicle state, and updates odometer mileage upon return.*
4. **D. Report Generation**  
   - **File:** [`backend/app/Services/ReportBuilder.php`](file:///c:/rvms%20capstone/backend/app/Services/ReportBuilder.php)  
   - **Caption:** `Listing 4. Operational Report and Summary Query Builder`  
   - **Brief:** *Gathers and filters records across operational categories to compile itemized tables and summary statistics.*  
   - *Status:* A clean, 100% comment-free, condensed version has been prepared and verified.

---

## 4. Technical Diagrams Status & Quality Enhancements

All diagrams in `skills/diagrams/` have been processed with Python Pillow:
- Edge Sharpening: Unsharp Masking (`radius=1.5–2.0`, `percent=175–195%`)
- Pure `#FFFFFF` background normalization and rich line darkening
- Injected `300 DPI` physical resolution metadata for crisp rendering in Word, Google Docs, and PowerPoint.

| Diagram File | Dimensions | Status |
|---|---|---|
| [`current-system-arch.png`](file:///c:/rvms%20capstone/skills/diagrams/current-system-arch.png) | 2980 × 1562 | **Enhanced & Embedded in Pitch DOCX (Slide 5)** |
| [`current-con-diag.png`](file:///c:/rvms%20capstone/skills/diagrams/current-con-diag.png) | 3054 × 1181 | **Enhanced & Embedded in Pitch DOCX (Slide 6)** |
| [`current-driver-dfd.png`](file:///c:/rvms%20capstone/skills/diagrams/current-driver-dfd.png) | 2396 × 2028 | Enhanced at 300 DPI (Ready for Slide 7) |
| [`current-admin-dfd.png`](file:///c:/rvms%20capstone/skills/diagrams/current-admin-dfd.png) | 4924 × 3751 | Enhanced at 300 DPI (Ready for Slide 8) |
| [`current-fdd.png`](file:///c:/rvms%20capstone/skills/diagrams/current-fdd.png) | 3067 × 3266 | Enhanced at 300 DPI (Ready for Slide 9) |
| [`current-admin-erd.png`](file:///c:/rvms%20capstone/skills/diagrams/current-admin-erd.png) | 1242 × 1784 | Enhanced at 300 DPI (Ready for Slide 10/11) |

*(Note: Raw originals are safely preserved as `*.original.png` in `skills/diagrams/`).*

---

## 5. Presentation Pitch Document State (`RVMS_Final_Defense_Pitch.docx`)

The file [`RVMS_Final_Defense_Pitch.docx`](file:///c:/rvms%20capstone/RVMS_Final_Defense_Pitch.docx) (with its synchronized mirror [`RVMS_Final_Defense_Pitch_Updated.docx`](file:///c:/rvms%20capstone/RVMS_Final_Defense_Pitch_Updated.docx) used to bypass WPS Office file locking) currently contains 60 paragraphs:
- **Slide 1:** Problem (Interviews + 3 RRL clusters: Mimbala/Vinay, Crespo/Gallega, Bolaños/Nerona)
- **Slide 2:** Solution (Mobile App + Web Dashboard features)
- **Slide 3:** Objectives (Main + 4 Specific Objectives)
- **Slide 4:** Live System Demonstration
- **Slide 5: SYSTEM ARCHITECTURE**  
  - *Embedded Image:* High-res `current-system-arch.png` (6.2" width, 300 DPI)
  - *Verbal Script:* Grounded 3-tier architecture explanation + FCM push messaging. Tone avoids overpromising buzzwords.
- **Slide 6: CONTEXT DIAGRAM**  
  - *Embedded Image:* High-res `current-con-diag.png` (6.2" width, 300 DPI)
  - *Verbal Script:* Process 0 system boundary, encapsulation of database, itemized 20 data flows (7 Driver, 13 Admin), and multi-agency tenant isolation (`agency_id`).

---

## 6. Immediate Next Steps (When Resuming)

When you return to continue this task:
1. **Slides 7 & 8 (Data Flow Diagrams in Pitch Presentation):**
   - Insert `current-driver-dfd.png` (Slide 7: Mobile Driver Operations DFD) and `current-admin-dfd.png` (Slide 8: Web Administrative Operations DFD) into `RVMS_Final_Defense_Pitch.docx`.
   - Provide the verbal defense pitch scripts detailing the 5 Mobile processes vs. the 8 Admin processes, demonstrating strict conservation of data flows across persistent data stores `D1` through `D8`.
2. **Slide 9 (Functional Decomposition Diagram):**
   - Insert `current-fdd.png` and add the 4-branch structural explanation (User, Vehicle, Maintenance, Reporting/Notifications).
3. **Slides 10 & 11 (Entity Relationship Diagrams):**
   - Address the MySQL Workbench ERD screenshot provided in chat (contains the 7 core tables: `inspection_checklist_items`, `inspection_items`, `inspections`, `users`, `vehicles`, `agencies`, `damage_reports`).
   - Insert the driver-side and fleet-side ERD images and provide the 3NF relational normalization and tenant isolation defense script.
4. **Append Chapter 5 Relevant Source Code to Manuscript:**
   - Append listings A, B, C, D into [`skills/rvms-source-of-truth.md`](file:///c:/rvms%20capstone/skills/rvms-source-of-truth.md) under Chapter 5.
