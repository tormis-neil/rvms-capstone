# RESCUE VEHICLE MANAGEMENT SYSTEM
## Project Source of Truth

**Capstone Project** | Northwest Samar State University | Calbayog City  
**Degree:** Bachelor of Science in Information Technology and Bachelor of Science in Information Systems  
**Proponents:** Neil Mayo C. Tormis, Jenny Rose C. Monticod, Jhon Lex C. Mahait, Sim Harold J. Doren, Christian Jay F. Abarro  
**Target Completion:** May 2027  

> **Notice:** This document is the verified textual source of truth for the Rescue Vehicle Management System (RVMS) capstone manuscript (Chapters 1, 3, and 4), updated to match `rvms-capstone (2).pdf`.

---

## CHAPTER 1 — INTRODUCTION

### Project Context

Government and emergency vehicle fleets in many countries still rely on manual recording systems and disconnected documentation practices that create recurring gaps in vehicle readiness and maintenance accountability. Vinay M. G. et al. (2025) found that manual vehicle information processes produce inconsistent records and poor data accessibility, while Richard Gorham et al. (2025) noted that the absence of centralized vehicle and driver databases weakens long-term monitoring and record validation. Crespo del Castillo and Parlikad (2024) determined that inadequate maintenance scheduling increases the risk of unexpected vehicle failures and operational downtime, and Carlos Bolaños et al. (2022) reported that fleet environments without centralized dispatch coordination suffer from poor operational visibility and reduced accountability. Philippine-based research shows the same problems. Mimbala et al. (2024) observed that manual logbook-based vehicle monitoring in institutional settings leads to inaccurate records and data loss, and Gallega (2025) noted that organized preventive maintenance scheduling is necessary for sustaining vehicle reliability. Nerona et al. (2025) found that manual administrative reporting increases the difficulty of consolidating and retrieving operational records within local government, while Intal et al. (2019) noted that maintenance monitoring deteriorates when agencies rely on paper-based and disconnected workflows.

In Calbayog City, four government agencies (the CDRRMO, BFP, CHO, and PNP) collectively operate ambulances, fire trucks, patrol cars, and other rescue vehicles deployed for emergency response across the city's barangays. Interviews conducted with officers from each agency confirmed that all four rely on manual, paper-based practices with no shared centralized system in place. The BFP documents daily vehicle inspections using a paper-based Motor Vehicle Daily Safety Checklist following the BLOWBAGETS protocol and records defect and maintenance history in a physical Motor Vehicle Ledger Card, while the CDRRMO and CHO depend on informal, driver-triggered defect reporting with no standardized daily inspection form, and the PNP communicates maintenance concerns verbally with no formal documentation at the station level. Fleet availability monitoring is no more systematic: the BFP texts vehicle status to the LGU after each maintenance event, CDRRMO personnel track availability through group chats and manual vehicle labeling, and CHO staff confirm vehicle readiness by sight since all three ambulances share the same compound. All four agencies maintain vehicle records, driver assignments, and license expiration dates in separate paper records that are not consolidated or searchable, and the PNP is the only agency with a digital tool for vehicle management, a Preventive Maintenance System (PMS) that covers only repair record logging, while the CDRRMO separately operates a data management software for emergency response analytics rather than fleet maintenance monitoring. An interview with the City GSO Motorpool further confirmed that damage descriptions submitted by agency officers are sometimes incomplete, as the Motorpool team regularly discovers additional defects during pre-inspection that were not included in the original report. In a setting where vehicle readiness directly affects emergency response outcomes, these documentation gaps and the absence of a centralized monitoring system across all four agencies create operational risk that manual records and informal communication cannot consistently address.

To address these conditions, this study proposes the development of the Rescue Vehicle Management System (RVMS), a two-platform digital solution consisting of an Android mobile application for Authorized Drivers and a web-based administrative dashboard for Agency Administrators. The mobile application allows drivers to view their assigned vehicle's details, submit digital BLOWBAGETS daily inspections, file damage reports with photo attachments, and receive notifications for preventive maintenance schedules and vehicle status changes. The administrative dashboard allows Agency Administrators to manage vehicle and driver records, monitor license expiry, review inspection results and damage reports, log and close dispatch activities, schedule and track preventive maintenance, log repair activities, and generate printable reports for inspections, damage reports, maintenance history, preventive maintenance, dispatch logs, and vehicle status. Each Agency Administrator's access is limited to their own agency's records, keeping vehicle data, inspection submissions, and dispatch logs separated by agency within the same system. The Rescue Vehicle Management System targets the CDRRMO, BFP, CHO, and PNP as participating agencies, with the aim of replacing their current paper forms, verbal reporting, and fragmented monitoring practices with a single digital platform through which vehicle data is consistently recorded and accessible to both drivers and administrators.

---

### Purpose and Description

The Rescue Vehicle Management System is a two-platform digital solution developed to centralize vehicle and driver record management, daily inspection monitoring, defect and damage reporting, repair logging, preventive maintenance scheduling, dispatch logging, and report generation for the CDRRMO, BFP, CHO, and PNP in Calbayog City. The system has two user-facing platforms: an Android mobile application for Authorized Drivers and a web-based administrative dashboard for Agency Administrators, with each Agency Administrator's access limited to records belonging to their own agency. Through the mobile application, Authorized Drivers can view their assigned vehicle's details and current status, submit digital BLOWBAGETS inspections with per-item status entries and remarks for flagged items, file damage reports with descriptions and optional photo attachments, and receive in-app notifications for preventive maintenance schedules and vehicle status updates. Through the web dashboard, Agency Administrators can manage vehicle and driver records, monitor automated license expiry alerts, review submitted inspection results and damage reports, log repair activities, track preventive maintenance schedules, and record and close dispatch activities. A dashboard summary screen displays current counts for vehicle operational statuses, total drivers, total vehicles, expiring licenses, and pending damage reports, together with the most frequently reported issues, while the report generation module allows Agency Administrators to produce filtered, printable records for inspections, damage reports, repair and maintenance history, preventive maintenance, dispatch logs, and vehicle status. Vehicle operational status across both platforms uses four values: Operational, Dispatched, Not Operational, and Under Preventive Maintenance. Agency Administrators receive in-app notifications for newly submitted damage reports, new driver access requests, submitted inspections that report an issue, approaching or expired driver licenses, and preventive maintenance schedules with a Due Soon or Due status, as well as when another administrator is added to their agency, and may mark these notifications as read or clear the ones they have already read from their own notification list.

---

### Objectives

The main objective of this study is to develop a Rescue Vehicle Management System. Specifically:

1. To input vehicle and driver information.
2. To track vehicle maintenance.
3. To provide an admin module for dispatch and vehicle management.
4. To generate relevant reports.

---

### Scope and Limitations

The study covers the design and development of the Rescue Vehicle Management System for four government agencies in Calbayog City: the Bureau of Fire Protection, Philippine National Police, City Disaster Risk Reduction and Management Office, and City Health Office. The system consists of an Android mobile application for Authorized Drivers and a web-based administrative dashboard for Agency Administrators, with each Agency Administrator's access limited to records belonging to their own agency. Vehicle and driver record management will store vehicle details including vehicle type (selectable from standard emergency presets or custom-specified via an "Other" option to accommodate specialized agency vehicles), plate number, make, model, engine number, chassis number, current mileage, assigned agency, an assigned primary driver, and an optional secondary driver, alongside driver information covering name, agency, license number, license expiry date, and an optional TESDA National Certificate II (NC II, Driving) number with automated monitoring for approaching and expired driver licenses and NC II certificates using a warning period configured per agency. Authorized Drivers and Agency Administrators will also be able to update their own name, email address, and password without administrator approval. The Digital BLOWBAGETS Inspection module will allow drivers to submit daily vehicle inspections using twelve standard checklist items (Battery, Lights, Oil, Water, Brakes, Air, Gas, Engine, Tires, Power Steering, Horn/Siren, and Directional Signals), with two additional items (Hydraulic System and Fire Pump) applicable to BFP drivers; each item is assigned a status of OK or Has Issue, with remarks required for flagged items, and Agency Administrators can review inspection results and view inspection history per vehicle and per driver. The Damage Report Management module will allow drivers to submit standardized damage reports with the nature of damage, suspected defective parts, and optional photo attachments; each report carries a default status of Pending and is marked Reviewed by the Agency Administrator after assessment, which also includes updating the vehicle's operational status. Repair Logging will document repair activities with scope of work, parts replaced, optional cost, repair source (Internal Office, GSO Motorpool, or External Repair Shop), an optional supporting document that is required when the source is an external repair shop, assigned driver, and remarks, with vehicle operational status updated separately by the Agency Administrator after the repair is logged. Preventive Maintenance Scheduling will support mileage-based and time-based intervals per vehicle, with a configurable Due Soon warning threshold set by the Agency Administrator, and an optional supporting document (such as a pre-inspection checklist) attached when a schedule is created, PM status values of Upcoming, Due Soon, Due, and Completed, and completion fields for date serviced, the odometer reading at the time of service for mileage-based schedules, repair source, an optional supporting document that is required when the source is an external repair shop, parts replaced, and remarks; PM schedules are not auto-renewed and must be manually created for each maintenance cycle. Dispatch Logging will allow Agency Administrators to record vehicle dispatches with vehicle, driver, an optional second driver for missions crewed by two drivers, mission type (Fire Response, Medical Response, Rescue Operation, Patrol, Administrative Travel, or Others), location, date and time out, and an optional odometer reading at time out, and an optional supporting document (travel order) with the selected driver's license status shown when it is approaching expiry or has expired; vehicle status changes automatically to Dispatched when a dispatch is opened, and Agency Administrators are prompted to set the return status (Operational, Not Operational, or Under Preventive Maintenance) and to record an optional odometer reading at time in when the dispatch is closed with date and time in, with the vehicle's current mileage updated from the recorded time-in odometer reading. A dashboard summary will display current counts for Operational, Dispatched, Not Operational, and Under Preventive Maintenance vehicles, total drivers, total vehicles, expiring licenses, and pending damage reports, together with the most frequently reported inspection issues. Report generation will cover inspection records, damage and defect reports, repair and maintenance history, preventive maintenance records, dispatch logs, and vehicle status summaries, each accessible with date range, vehicle, driver, and other applicable filters, and each presenting summary statistics for the filtered records alongside the detailed listing. In-app notifications for Authorized Drivers will cover preventive maintenance reminders and vehicle status updates, while notifications for Agency Administrators will cover newly submitted damage reports, new driver access requests, submitted inspections in which one or more checklist items are flagged as Has Issue, approaching or expired driver licenses, preventive maintenance schedules with a Due Soon or Due status, and the addition of another administrator to their agency. Agency Administrators will also be able to mark their notifications as read and to clear notifications they have already read from their own notification list; unread notifications are retained, and clearing affects only the notification list of the Agency Administrator who performs it. Agency Administrators will also be able to set a new password for an Authorized Driver of their agency after confirming their own account password, so that an account holder who can no longer sign in can be restored to access.

The proposed system will be limited to rescue vehicle operations and maintenance monitoring for the four participating agencies within Calbayog City, and will support two user roles only: Authorized Drivers and Agency Administrators. GSO personnel, external mechanics, and third-party repair providers will not have direct access to the system. Agency Administrator accounts are provisioned within the system; an existing Agency Administrator may create additional Agency Administrator accounts for their own agency after confirming their own password, and only Authorized Drivers may self-register, and a self-registered driver account remains pending until an Agency Administrator of that agency approves or rejects it. The system will not send electronic mail or text messages, and therefore provides no emailed password reset links or one-time verification codes; password reset is performed within the system, with an Agency Administrator setting a new password only for an Authorized Driver of their own agency after confirming their own account password. An Agency Administrator cannot reset the password of another Agency Administrator; an Agency Administrator who can no longer sign in must be assisted through a recovery command run on the server by the personnel maintaining it. Both platforms require a stable internet connection for data submission, synchronization, notifications, and dashboard access. The mobile application supports Android devices only and will not be available on iOS platforms. The system will not include GPS tracking, GIS mapping, route navigation, route optimization, IoT-based vehicle diagnostics, telematics integration, fuel consumption monitoring, or automated dispatch recommendation features; odometer readings are entered manually by the Agency Administrator from the vehicle's own odometer and are not captured automatically through any device integration. Vehicle inspections, damage reports, repair records, and preventive maintenance data depend on manual user input, and mileage-based preventive maintenance tracking relies on current mileage values that are manually updated by Agency Administrators in the vehicle records or captured from the odometer reading recorded when a dispatch is closed. The dispatch module supports only dispatch logging and fleet availability monitoring and does not include real-time vehicle tracking, emergency communication systems, or automated dispatch assignment; the assignment of vehicles and drivers to missions remains dependent on each participating agency's internal procedures. The system assigns one primary driver per vehicle record and, optionally, one secondary driver, accommodating the two-driver arrangements reported by the CHO and the PNP; broader rotating or shift-based rosters of three or more drivers are not modeled on the vehicle record, and driver attribution in those cases relies on the dispatch record rather than the vehicle assignment. Vehicle condition terminology also varies across agencies; agency-specific status labels such as the BFP's "Serviceable" and "Unserviceable" classifications are not stored separately and are instead represented by the system's four standardized operational statuses. Passenger and patient details are not recorded by the system, as these fall outside vehicle management scope and avoid storing sensitive personal information. Although the system supports repair logging and vehicle status monitoring, actual repair validation, mechanical inspections, procurement, and external maintenance processes remain outside the system's scope. Vehicles under manufacturer warranty will be marked as Not Operational when damaged or under repair, with the actual repair process remaining dependent on authorized dealers or external service providers. Generated reports are based solely on encoded data and will not produce officially formatted government forms or legally standardized Commission on Audit documents. The study focuses on system development, workflow implementation, and pilot-level testing within the duration of the capstone project. Vehicle and driver records may be added and updated but not deleted. This is a deliberate constraint rather than an omission: every inspection, damage report, repair log, preventive maintenance schedule, and dispatch refers to a vehicle and a driver, and removing either would break the history that the system exists to preserve. A vehicle that leaves service is recorded through its operational status, and a driver who leaves the agency is recorded by reassigning their vehicles to another driver.

---

## CHAPTER 3 — TECHNICAL BACKGROUND

This chapter outlines the software, hardware, and human resources requirements necessary for the successful development and implementation of the proposed system. The specifications listed herein will represent the requirements necessary for each use case to function correctly, and they will guide the entire development and implementation process of the Rescue Vehicle Management System.

The technical background of the study will establish the technological foundation necessary for the development and implementation of the proposed Rescue Vehicle Management System. Since the system will support centralized vehicle monitoring, fleet availability monitoring, maintenance tracking, dispatch logging, and report generation across multiple government agencies, appropriate software tools, hardware resources, and human expertise will be required to ensure system functionality, reliability, and operational efficiency. The identified technical requirements were selected based on the operational workflows gathered from the BFP, PNP, CDRRMO, and City Health Office during the data gathering phase of the study. These requirements will guide the researchers in designing a system capable of supporting digital vehicle inspection, defect reporting, dispatch monitoring, preventive maintenance tracking, dispatch logging, and report generation for rescue vehicle operations across the participating agencies.

---

**Table 1. Technical Requirements for the Development of Rescue Vehicle Management System**

| Category | Specifications | Description |
|---|---|---|
| **Software** | | |
| Android Studio | Android Studio Meerkat (2024.3.1)+ | Used for developing the mobile application. |
| Kotlin | Kotlin 1.9+ | Main programming language for Android development. |
| Jetpack Compose | Compose BOM 2024.09.00+ | UI toolkit for creating Android application interfaces. |
| Laravel | Laravel 11 (PHP 8.2+) | Framework used for backend and web dashboard development. |
| MySQL | MySQL 8.0+ | Relational database used for storing system records and reports. |
| Visual Studio Code | Visual Studio Code 1.95+ | Code editor used for backend and web development. |
| Bootstrap | Bootstrap 5.3+ | Framework used for designing responsive web interfaces. |
| Firebase Cloud Messaging (FCM) | FCM SDK (Firebase Android SDK 32.0.0+ / Firebase PHP via HTTP v1 API) | Used for mobile push notifications such as preventive maintenance reminders and vehicle status updates. |
| **Hardware** | | |
| Desktop Computer/Laptop | Intel Core i5 or higher, 8 GB RAM, Windows 10/11 | Used for running development tools and system testing. |
| Android Device | Android 8.0 (Oreo) or higher | Used for testing the mobile application. |
| **Human Resource** | | |
| Project Leader | Leadership and Project Management | Oversees project tasks, timeline, and team coordination. |
| Software Programmer | Programming and System Development | Handles the development of the mobile app, backend API, and web dashboard. |
| Researcher | Research and Documentation Skills | Conducts research, data gathering, and documentation. |

Table 1 presents the technical requirements for the development of the Rescue Vehicle Management System. The study will utilize both mobile and web technologies to support centralized vehicle monitoring, inspection, and maintenance management. For mobile application development, Android Studio Meerkat (2024.3.1)+ will serve as the primary Integrated Development Environment (IDE), while Kotlin 1.9+ will be used as the main programming language due to its compatibility with modern Android development practices. Jetpack Compose will be integrated as the Android UI toolkit to create responsive and declarative user interfaces for the authorized drivers' mobile application. Firebase Cloud Messaging (FCM) will be integrated to support the push notification system, enabling the delivery of real-time alerts to Authorized Drivers through the mobile application for upcoming preventive maintenance schedules and vehicle status changes. Agency Administrators will receive notifications for newly submitted damage reports, new driver access requests, submitted inspections that report an issue, approaching or expired driver licenses, and preventive maintenance schedules with a Due Soon or Due status.

For backend and web-based development, Laravel 11 with PHP 8.2+ will be used as the framework for building the REST API and administrative dashboard because of its scalability, security, and efficient development features. MySQL 8.0+ will function as the relational database management system for storing and organizing vehicle and driver records, BLOWBAGETS inspection results, defect and damage reports, repair logs, preventive maintenance schedules, dispatch records, and the records from which system reports are generated. Visual Studio Code 1.95+ will be used as the primary code editor for developing and maintaining the Laravel backend, API services, and frontend components. Additionally, Bootstrap 5.3+ will be utilized to design a responsive and user-friendly interface for Agency Administrators accessing the web-based dashboard.

In terms of hardware requirements, a desktop computer or laptop equipped with at least an Intel Core i5 processor or equivalent, 8 GB RAM, and Windows 10/11 operating system will be required to efficiently run Android Studio, Visual Studio Code, database services, and other development tools simultaneously. A physical Android device running Android 8.0 (Oreo) or higher will also be necessary for actual device testing to ensure the reliability, compatibility, and performance of the mobile application beyond emulator-based testing.

The human resources involved in the project include the Project Leader, Software Programmer, and Researcher. The Project Leader will oversee project management activities such as task delegation, timeline monitoring, and team coordination to ensure the successful completion of the study. The Software Programmer will be responsible for the full technical implementation of the system, including the Android mobile application, backend API, database integration, and web dashboard development. Meanwhile, the Researcher will manage the documentation and academic components of the study, including literature review, data gathering, analysis, and ensuring that the research outputs comply with academic standards and institutional requirements.

---

**Table 2. Technical Requirements for the Implementation of Rescue Vehicle Management System**

| Category | Specifications | Description |
|---|---|---|
| **Software** | | |
| Rescue Vehicle Management System (Mobile App) | Android 8.0+ | Mobile application used for vehicle information viewing, digital inspections, defect reporting, preventive maintenance and vehicle status notifications. |
| Rescue Vehicle Management System (Web Dashboard) | Any modern browser (Chrome, Firefox, Edge) | Web dashboard used for vehicle, driver, dispatch records, maintenance, and report management. |
| **Hardware** | | |
| Android Device | Android 8.0 or higher, internet-capable | Used by authorized drivers to access the mobile application. |
| Desktop Computer/Laptop | Any modern desktop or laptop with internet access | Used by Agency Administrators to access the web-based dashboard. |
| **Human Resource** | | |
| Authorized Driver | Vehicle Inspection and Reporting | Conducts digital inspections, submits defect reports, and monitors vehicle status. |
| Agency Administrator | Vehicle Management and Monitoring | An authorized account for managing vehicles, maintenance, dispatch, and operational records within an agency. |

Table 2 presents the technical requirements for the implementation of the Rescue Vehicle Management System. The system will be implemented through two major platforms: a mobile application intended for Authorized Drivers and a web-based dashboard designed for Agency Administrators. The mobile application will operate on Android devices running Android 8.0 or higher, enabling drivers to conduct digital vehicle inspections based on the BLOWBAGETS protocol, submit defect and damage reports, view assigned vehicle information, monitor vehicle status, and receive preventive maintenance reminders and status update notifications. The web-based dashboard, accessible through modern browsers such as Google Chrome, Mozilla Firefox, and Microsoft Edge, will allow Agency Administrators to manage vehicle and driver records, log dispatch records, monitor fleet availability, review inspection results and defect reports, schedule preventive maintenance, log repair actions, and generate operational reports.

For hardware requirements, an internet-capable Android smartphone will be required for Authorized Drivers to access and use the mobile application during pilot testing and actual implementation. Meanwhile, Agency Administrators will utilize desktop or laptop computers with internet access to operate the web dashboard efficiently. These devices will support real-time monitoring, report management, and centralized coordination of rescue vehicle maintenance activities.

The implementation phase will involve two primary user roles: Authorized Drivers and Agency Administrators. Authorized Drivers are expected to possess vehicle inspection and reporting skills to properly conduct digital BLOWBAGETS inspections and submit accurate defect and damage reports through the mobile application. Agency Administrators will require vehicle and fleet management skills to oversee vehicle assignments, dispatch logging, fleet availability monitoring, preventive maintenance scheduling, repair logging, and report generation through the web dashboard. These human resources are essential to ensure the effective operation and sustainability of the Rescue Vehicle Management System during pilot testing and actual deployment.

---

### Schedule of Activities

A project timeline represented through a Gantt chart will guide the development and completion of the Rescue Vehicle Management System. The project activities will follow the Prototyping Model of the Software Development Life Cycle (SDLC), consisting of Planning and Data Gathering, Prototype Design, Evaluation, and Refinement, System Development and Implementation, and Testing and Deployment phases spanning May to November 2026.

| Activities | MAY 2026 | JUN 2026 | JUL 2026 | AUG 2026 | SEPT 2026 | OCT 2026 |
|---|:---:|:---:|:---:|:---:|:---:|:---:|
| Planning & Data Gathering | ● | | | | | |
| Prototype Design, Evaluation, and Refinement | | ● | ● | | | |
| System Development and Implementation | | | ● | ● | ● | |
| Testing and Deployment | | | | | ● | ● |

**Figure 1. Schedule of Activities for the Development and Implementation of Rescue Vehicle Management System**

The Planning and Data Gathering phase, covering May, will focus on initial system planning, background research, review of related literature and existing operational procedures, identification of system scope, and workflow analysis. This phase will also include the drafting of Chapters 1 to 3, which will cover the introduction of the problem, analysis of existing maintenance and dispatch procedures across agencies, and definition of technical requirements. In keeping with the iterative nature of the Prototyping Model, requirements will be gathered through structured interviews with key stakeholders from the Philippine National Police, Bureau of Fire Protection, City Disaster Risk Reduction and Management Office, and City Health Office, conducted in two rounds — an initial requirements interview and a follow-up interview that addresses gaps identified in the first — so that the common maintenance and dispatch practices of the four agencies can be validated and the proposed system features refined based on actual agency operations.

The Prototype Design, Evaluation, and Refinement phase, covering June to July, will focus on the design of the system prototype, validation of system workflows, and refinement of requirements based on stakeholder feedback. This phase will involve the creation of UI/UX mockups and interactive prototypes for both the Android mobile application and the web-based administrative dashboard, ensuring that the proposed system layout and navigation align with the operational needs of Authorized Drivers and Agency Administrators. Consistent with the Prototyping Model, the interactive prototype will be presented to representatives of the participating agencies for evaluation. Feedback, comments, and recommendations gathered during the evaluation process will be analyzed and incorporated into the prototype design and system requirements, ensuring that the proposed solution accurately reflects the operational needs and shared practices of the four agencies before full-scale development begins.

The System Development and Implementation phase, spanning July to September, will cover the development of the validated prototype into a fully functional Rescue Vehicle Management System. This phase will include the development and integration of the web-based administrative dashboard, Android mobile application for drivers, centralized database, digital inspection module, defect and damage reporting module, repair logging module, preventive maintenance scheduling module, dispatch logging and fleet monitoring module, notification system, and report generation functionalities. Following the iterative and feedback-driven nature of the Prototyping Model, continuous refinement, debugging, and functionality verification will be conducted throughout development to ensure that the implemented features align with user requirements and operational expectations.

Finally, the Testing and Deployment phase, covering September to October, will include unit testing, integration testing, user acceptance testing with the participating agencies, bug fixing, pilot testing, deployment preparation, and finalization of documentation including Chapters 4 and 5. This phase will also include system validation, preparation for final implementation, and readiness activities for the final presentation and system defense. The results of testing and evaluation will be used to verify that the system satisfies the functional requirements identified during the earlier phases of development.

---

## CHAPTER 4 — METHODOLOGY

This chapter presents the methodology, results, and discussion of the Rescue Vehicle Management System. It describes the structured approach the proponents followed in determining the needs of the participating agencies, documenting the system requirements, and designing the technical structure of the solution, and it discusses the outputs produced at each stage. The methodology is presented to demonstrate that the development of the Rescue Vehicle Management System was well-planned, credible, and repeatable, and to establish a clear foundation for the design, development, and testing phases of the study.

The development of the Rescue Vehicle Management System followed the Prototyping Model of the Software Development Life Cycle (SDLC). In this model, the proponents first gather requirements, then design and build a working prototype that the intended users evaluate; the feedback gathered is used to refine the requirements and the prototype across successive iterations until the design is validated, after which the actual system is developed, tested, and deployed. The Prototyping Model was selected because the Rescue Vehicle Management System is being built for four government agencies whose common dispatch and vehicle-maintenance processes had to be reconciled into a single centralized platform, a goal that required continuous feedback and validation from the client agencies rather than a one-time, fixed specification. The proponents conducted two rounds of interviews; an initial requirements interview and a follow-up interview that addressed gaps identified in the first, and developed an interactive prototype that simulates the complete set of workflows. The prototype was presented to representatives of the participating agencies for evaluation, and the feedback gathered was used to refine both the prototype and the system requirements before full-scale development. This iterative and feedback-driven approach ensures that the resulting system accurately reflects the shared operational needs of the four participating agencies while reducing the risk of implementing features that do not align with actual agency operations.

Following this model, the development of the Rescue Vehicle Management System was organized into the phases reflected in the project's Schedule of Activities presented in Chapter 3. The Planning and Data Gathering phase corresponds to the requirements analysis phase, during which the proponents conducted interviews, reviewed existing records and forms, and analyzed the operational workflows of the participating agencies. The Prototype Design, Evaluation, and Refinement phase corresponds to prototype design and validation, in which the system architecture and an interactive prototype were designed and are being evaluated by the client agencies and the capstone adviser, whose feedback is used to refine the requirements and the prototype before development. The System Development and Implementation phase corresponds to the construction and integration of the system modules, while the Testing and Deployment phase covers unit, integration, and user acceptance testing, pilot testing, and the finalization of the system. The sections that follow present the outputs of the requirements analysis and design phases of this process.

---

### Requirements Analysis

Requirements analysis is the process of determining the needs and expectations of the intended users of the system through close communication with stakeholders, and of resolving conflicts and gaps so that a clear and agreed set of requirements can be established. For the Rescue Vehicle Management System, this phase aimed to identify the operational problems experienced by the four participating rescue agencies in Calbayog City — the Bureau of Fire Protection (BFP), the Philippine National Police (PNP), the City Disaster Risk Reduction and Management Office (CDRRMO), and the City Health Office (CHO) — and to translate those problems into the specific functions the system must perform to meet the objectives set in Chapter 1.

To gather the requirements, the proponents conducted structured interviews with key personnel from each of the four agencies, including drivers, logistics officers, supply officers, and operations officers, as well as personnel from the City General Services Office (GSO) Motorpool. The interviews examined how each agency records vehicle and driver information, performs daily vehicle inspections, reports defects and damage, processes repairs and preventive maintenance, logs dispatches, and monitors vehicle availability. In addition to the interviews, the proponents reviewed existing documentation used by the agencies, such as the BFP Motor Vehicle Daily Safety Checklist, the Motor Vehicle Ledger Card, and other manual logbooks and forms, and observed the current vehicle monitoring practices to understand the actual flow of information across personnel and offices. These techniques were used together so that the requirements would be grounded in the real operational workflows of the participating agencies rather than on assumptions.

The gathered data were then analyzed to identify common practices, recurring problems, and gaps that the proposed system needed to address. The analysis revealed that all four agencies maintain vehicle records, driver assignments, license information, vehicle status, and maintenance activities, but that these are managed almost entirely through paper forms, logbooks, spreadsheets, verbal reporting, phone calls, text messages, and group chats. Because this information is scattered across separate records and offices, the agencies cannot readily determine which vehicles are operational, dispatched, under repair, or due for maintenance, and damage reports submitted to the GSO Motorpool are sometimes incomplete. Differences among the agencies were also identified and reconciled during the analysis: only the BFP uses a formal daily checklist following the BLOWBAGETS protocol with two additional items, the Hydraulic System and Fire Pump, while the other agencies rely on informal checks; the CHO follows a rotating, shift-based driver arrangement; and the PNP and CDRRMO already operate limited digital tools that do not cover full fleet maintenance monitoring. These variations were taken into account so that the system could accommodate a standard workflow common to all agencies while still supporting agency-specific needs.

From this analysis, the proponents determined and categorized both the user and system requirements in line with the four objectives of the study: to input vehicle and driver information, to track vehicle maintenance, to provide an administrative module for dispatch and vehicle management, and to generate relevant reports. The requirements were organized into functional capabilities — such as record management, digital inspection, damage reporting, repair and preventive maintenance tracking, dispatch logging, fleet monitoring, report generation, and notifications — and into quality attributes such as performance, security, usability, reliability, and compatibility. This categorized set of requirements served as the Software Requirements Specification that guided the documentation, design, and development of the Rescue Vehicle Management System, and it is presented in detail in the Requirements Documentation section that follows.

---

### Requirements Documentation

The Requirements Documentation section outlines the essential features, functions, and constraints of the Rescue Vehicle Management System, which serve as the foundation for its design and development. Building on the needs identified in the Requirements Analysis section, this documentation provides an organized record of what the system is expected to achieve so that all stakeholders share a clear and common understanding of its intended behavior. It is presented in two parts: the functional requirements, which describe the specific behaviors and features the system must provide, and the non-functional requirements, which define the quality standards the system must satisfy, including performance, security, usability, reliability, and compatibility. Clearly defining these requirements helps the proponents remain focused during development, avoid misunderstandings, and build a system that is useful, dependable, and appropriate for rescue vehicle operations.

#### Functional Requirements

The functional requirements define what the Rescue Vehicle Management System is able to do: the specific tasks it performs for Authorized Drivers in the field and for the Agency Administrators who manage each agency's vehicles. These requirements are based on the interviews conducted with officers of the four participating agencies (CDRRMO, BFP, CHO, and PNP) and with the City GSO Motorpool, and they cover maintaining accurate vehicle and driver records, documenting inspections and repairs, monitoring vehicle availability, and keeping users informed of important events. Each requirement is written as a statement of what the system shall do and is presented in Table 3.

**Table 3. Functional Requirements for the Rescue Vehicle Management System**

| Code | Functional Requirement | Description |
|---|---|---|
| **FR-01** | User Login | The system shall allow a user to sign in using an email and password. The system shall verify the entered details. The system shall identify whether the user is an Authorized Driver or an Agency Administrator and open the platform for that role: the mobile application for drivers and the web dashboard for administrators. |
| **FR-02** | User Roles and Agency Access | The system shall support only two user roles: Authorized Driver and Agency Administrator. The system shall allow each Agency Administrator to view and manage only the records that belong to their own agency. |
| **FR-03** | Driver Registration and Approval | The system shall allow a driver to register by choosing their agency and entering their details. The system shall keep a self-registered driver account pending until an Agency Administrator of that agency approves or rejects it. The system shall allow an Agency Administrator to add a driver directly. |
| **FR-04** | Create Administrator Account | The system shall allow an Agency Administrator to create another Agency Administrator account for their own agency after confirming their own password. The system shall not allow a person to register themselves as an Agency Administrator. The system shall notify the other Agency Administrators of that agency when a new administrator is added. |
| **FR-05** | Profile Management | The system shall allow Authorized Drivers and Agency Administrators to update their own name, email address, and password without administrator approval. |
| **FR-06** | Password Recovery | The system shall allow an Agency Administrator to set a new password for an Authorized Driver of their own agency after confirming their own password. The system shall perform this reset inside the system, without sending an email or text message. The system shall not allow an Agency Administrator to reset the password of another Agency Administrator. |
| **FR-07** | Vehicle Record Management | The system shall allow Agency Administrators to add, update, and view vehicle records containing the vehicle type (selectable from emergency vehicle presets or custom-specified through an "Other" option), plate number, make, model, engine number, chassis number, current mileage, assigned agency, a primary driver, and an optional secondary driver. The system shall not allow vehicle records to be deleted, so that the inspection, damage, repair, preventive maintenance, and dispatch history linked to each vehicle stays complete. |
| **FR-08** | Driver Record Management | The system shall allow Agency Administrators to add, update, and view driver records containing the name, agency, license number, license expiry date, and an optional TESDA National Certificate II (NC II, Driving) number and expiry date. The system shall allow an Agency Administrator to assign a driver as the primary or secondary driver of one or more vehicles. The system shall not allow driver records to be deleted, so that the inspections and damage reports a driver submitted continue to identify their author. |
| **FR-09** | View Assigned Vehicle | The system shall allow Authorized Drivers to view, in the mobile application, the complete details and current operational status of every vehicle assigned to them as primary or secondary driver. |
| **FR-10** | License Expiry Monitoring | The system shall automatically detect driver licenses and NC II certificates that are approaching expiry or have expired. The system shall use a warning period that is configured per agency. The system shall show these licenses and certificates to the Agency Administrator in one consolidated monitoring view. |
| **FR-11** | Digital BLOWBAGETS Inspection | The system shall allow Authorized Drivers to submit a daily vehicle inspection using a checklist of 12 standard items: Battery, Lights, Oil, Water, Brakes, Air, Gas, Engine, Tires, Power Steering, Horn/Siren, and Directional Signals. The system shall add two more items, Hydraulic System and Fire Pump, for BFP drivers. The system shall require each item to be marked OK or Has Issue. The system shall require a remark for every item marked Has Issue. |
| **FR-12** | Inspection Monitoring | The system shall allow Agency Administrators to review submitted inspection results. The system shall allow Agency Administrators to view inspection history per vehicle and per driver. |
| **FR-13** | Damage Reporting | The system shall allow Authorized Drivers to submit a damage report containing the nature of the damage, the suspected defective parts, and an optional photo. The system shall set the status of every new damage report to Pending. |
| **FR-14** | Damage Report Management | The system shall allow Agency Administrators to review damage reports and mark them as Reviewed after assessment. The system shall allow the Agency Administrator to update the vehicle's operational status as part of that assessment. |
| **FR-15** | Repair Logging | The system shall allow Agency Administrators to log a repair with the scope of work, parts replaced, optional cost, repair source (Internal Office, GSO Motorpool, or External Repair Shop), assigned driver, and remarks. The system shall allow a supporting document to be attached to a repair log and shall require it when the repair source is an External Repair Shop. The system shall allow the Agency Administrator to update the vehicle's operational status after the repair is logged. |
| **FR-16** | Preventive Maintenance Scheduling | The system shall allow Agency Administrators to create a preventive maintenance (PM) schedule for a vehicle based on mileage or on time. The system shall allow the Agency Administrator to set the Due Soon threshold of each schedule. The system shall allow an optional supporting document, such as a pre-inspection checklist, to be attached when the schedule is created. The system shall automatically set the status of each schedule to Upcoming, Due Soon, or Due. The system shall not renew a schedule automatically; the Agency Administrator creates a new schedule for each maintenance cycle. |
| **FR-17** | Preventive Maintenance Completion | The system shall allow Agency Administrators to record the completion of a PM schedule with the date serviced, repair source, parts replaced, and remarks, and, for mileage-based schedules, the odometer reading at the time of service. The system shall allow a supporting document to be attached and shall require it when the repair source is an External Repair Shop. The system shall set the status of the schedule to Completed once its completion is recorded. |
| **FR-18** | Dispatch Logging | The system shall allow Agency Administrators to open a dispatch by entering the vehicle, driver, an optional second driver for a two-driver mission, mission type (Fire Response, Medical Response, Rescue Operation, Patrol, Administrative Travel, or Others), location, date and time out, and an optional odometer reading at time out. The system shall allow an optional supporting document, such as a travel order, to be attached. The system shall show the license status of the selected driver when it is approaching expiry or has expired. The system shall automatically set the vehicle's status to Dispatched when a dispatch is opened. |
| **FR-19** | Dispatch Closing | The system shall allow Agency Administrators to close a dispatch by entering the date and time in, an optional odometer reading at time in, and the return status of the vehicle (Operational, Not Operational, or Under Preventive Maintenance). The system shall update the vehicle's status to the return status chosen. The system shall update the vehicle's current mileage from the odometer reading at time in when one is recorded. |
| **FR-20** | Vehicle Availability Monitoring | The system shall allow Agency Administrators to view the current status of all vehicles in their own agency to support deployment decisions. |
| **FR-21** | Vehicle Status Management | The system shall keep one operational status for each vehicle: Operational, Dispatched, Not Operational, or Under Preventive Maintenance. The system shall show the same status on the mobile application and on the web dashboard. The system shall allow the status to be updated through damage report review, repair logging, and dispatch closing, and shall set it to Dispatched when a dispatch is opened. |
| **FR-22** | Dashboard Summary | The system shall show each Agency Administrator the current counts for their own agency: vehicles that are Operational, Dispatched, Not Operational, and Under Preventive Maintenance, total vehicles, total drivers, expiring licenses, and pending damage reports. The system shall show the most frequently reported inspection issues on the dashboard. |
| **FR-23** | Report Generation | The system shall allow Agency Administrators to generate printable reports for inspections, damage reports, repair and maintenance history, preventive maintenance, dispatch logs, and vehicle status. The system shall allow each report to be filtered by date range, vehicle, driver, and other applicable filters. The system shall show summary statistics for the filtered records together with the detailed listing. |
| **FR-24** | Driver Notifications | The system shall send in-app notifications to Authorized Drivers for preventive maintenance reminders and vehicle status updates. The system shall also deliver a push notification to the driver's mobile device for each of these notifications. |
| **FR-25** | Administrator Notifications | The system shall send in-app notifications to Agency Administrators when a damage report is submitted, when a driver requests access, when a submitted inspection has one or more items marked Has Issue, when a driver license is approaching expiry or has expired, when a PM schedule becomes Due Soon or Due, and when another administrator is added to their agency. |
| **FR-26** | Notification Management | The system shall allow Agency Administrators to mark their notifications as read. The system shall allow Agency Administrators to clear notifications they have already read. The system shall keep unread notifications. The system shall apply the clearing only to the notification list of the administrator who cleared them. |

Table 3 presents the functional requirements of the Rescue Vehicle Management System, arranged to follow how the system is actually used. Access begins with authentication: an Authorized Driver or an Agency Administrator signs in, and the system identifies the user's role, directs them to the appropriate platform, and limits each Agency Administrator to the records of their own agency. New drivers may register and remain pending until their agency's administrator approves them, administrators may create additional administrators, all users may update their own accounts, and administrators may set a new password for a driver who can no longer sign in. Once inside, administrators maintain the agency's vehicle and driver records, while the system automatically monitors licenses and NC II certificates that are approaching or past their expiry. In the field, drivers use the mobile application to submit daily BLOWBAGETS inspections and to report any damage, which administrators then review and act upon. The system further supports vehicle care by logging repairs, scheduling preventive maintenance, and recording its completion. When a vehicle is dispatched, the system records its departure and return and updates its status and mileage, allowing administrators to see which vehicles are available. Finally, the dashboard gives an overview of the fleet, reports can be generated for documentation, and notifications keep drivers and administrators informed of time-sensitive events. Together, these functions replace the agencies' paper-based and fragmented practices with a single connected system, and they serve as the foundation for the system's design, development, and testing.

---

#### Non-Functional Requirements

The non-functional requirements describe the quality attributes of the Rescue Vehicle Management System. Unlike the functional requirements, which specify what the system must do, the non-functional requirements define how well the system must perform under different conditions to be effective, secure, and usable for rescue vehicle operations. These attributes were identified from the operational conditions of the participating agencies, particularly the need for accurate and private records and the reality that many users are field personnel rather than technical experts. The non-functional requirements are presented in Table 4.

**Table 4. Non-Functional Requirements for the Rescue Vehicle Management System**

| Code | Non-Functional Requirement | Description |
|---|---|---|
| **NFR-01** | Performance | The system should respond quickly to user actions, loading dashboards, lists, and reports without noticeable delay, even when several agency users are working at the same time. |
| **NFR-02** | Security | The system should keep data safe through secure logins, hashed passwords, role-based access, separation of each agency's data, and encrypted communication, so every agency's records stay private. |
| **NFR-03** | Usability | The system should be simple and easy to use on both platforms, letting drivers and administrators finish common tasks in as few steps as possible, with large, easy-to-tap buttons on the mobile app. |
| **NFR-04** | Reliability | The system should record information accurately and show the same vehicle status on both platforms, with dependable delivery of notifications for time-sensitive events. |
| **NFR-05** | Compatibility | The mobile app should run on Android 8.0 (Oreo) and later, and the web dashboard should work on common browsers like Google Chrome, Mozilla Firefox, and Microsoft Edge. |

Table 4 presents the non-functional requirements, which describe the qualities that make the system dependable in actual use. Performance ensures that pages, record lists, and reports load without noticeable delay, even when several agency users are working at the same time. Security keeps each agency's records private and protected so that only authorized users can access them. Usability provides a simple and consistent interface that allows drivers and administrators to complete common tasks in as few steps as possible, including on mobile devices used in the field. Reliability ensures that information is recorded accurately and that the same vehicle status appears on both platforms, with dependable delivery of time-sensitive notifications. Compatibility ensures that the mobile application runs on ordinary Android devices and that the web dashboard operates on the common browsers the agencies already use. Together with the functional requirements, these quality attributes guided the architectural and design decisions discussed in the following section.

---

### Design of Software, Systems, Product, and/or Processes

This section presents the technical design of the Rescue Vehicle Management System and the structures involved in achieving that design in accordance with the documented requirements. It describes, through diagrams and supporting narrative, how the system was organized so that its components work together to satisfy both the functional and non-functional requirements established in the preceding sections. The design is presented in three parts: the system architecture, which provides the foundational blueprint; the process model, comprising the context diagram, data flow diagram, and functional decomposition diagram; and the data model, comprising the entity-relationship diagram and data dictionary.

#### System Architecture

The system architecture serves as the overall structure of the Rescue Vehicle Management System, illustrating how users, applications, the application server, the centralized database, and the notification service interact to support vehicle monitoring, inspection, maintenance, and dispatch management. The architecture is composed of two user roles, namely the Authorized Driver and the Agency Administrator, who access the system through their respective platforms: an Android mobile application for drivers and a web dashboard for administrators. An application server receives the requests from both platforms, applies the system's rules, including limiting each Agency Administrator to the records of their own agency, and reads from and writes to a centralized MySQL database that stores all system records. Firebase Cloud Messaging (FCM) is integrated to deliver push notifications to the drivers' mobile devices. This architecture supports centralized data management and allows both platforms to work from the same stored data. The system architecture of the Rescue Vehicle Management System is shown in Figure 2.

**Figure 2. System Architecture of the Rescue Vehicle Management System**  
*(See system architecture diagram in manuscript.)*

As illustrated in Figure 2, the Rescue Vehicle Management System follows a centralized client–server architecture in which two client platforms communicate with a single application server and a shared database. The Authorized Driver interacts with the system through the mobile application, submitting inspections and damage reports and, in return, viewing vehicle information and notifications. The mobile application sends the inspection and damage data to the application server, which returns vehicle data and notifications to the application. The Agency Administrator interacts with the system through the web dashboard, managing records, dispatch, and maintenance, reviewing inspections and damage reports, monitoring vehicle status, generating reports, and viewing notifications. The web dashboard sends requests and record updates to the application server, which returns the requested records and notifications. For both platforms, the application server stores and updates records in the centralized database and retrieves the records it needs, so that the mobile application and the web dashboard work from the same data. When an event requires a driver alert, the application server saves the notification in the database and triggers a push notification through Firebase Cloud Messaging (FCM), which delivers it to the driver's mobile application. Because FCM delivers messages on a best-effort basis and needs the device to be online, each notification also remains in the driver's in-app notification list. Agency Administrators view their notifications on the web dashboard. Through this arrangement, the architecture keeps vehicle records consistent across both platforms and lets drivers receive timely alerts without manually checking the system.

#### Context Diagram

The context diagram provides the highest-level view of the Rescue Vehicle Management System. It shows the whole system as a single process, numbered 0, together with the outside parties that exchange data with it, and in doing so sets the boundary of the system: what belongs inside it and what lies outside it. The database is part of the system and is therefore not shown at this level; it appears in the more detailed diagrams that break the system down further. The context diagram of the Rescue Vehicle Management System is shown in Figure 3.

**Figure 3. Context Diagram of the Rescue Vehicle Management System**  
*(See context diagram in manuscript.)*

Figure 3 presents the context diagram of the Rescue Vehicle Management System. The RESCUE VEHICLE MANAGEMENT SYSTEM process, labeled 0, is at the center of the diagram, and two external entities are placed around it: the Authorized Driver and the Agency Administrator. The Authorized Driver reaches the system through the mobile application and the Agency Administrator through the web dashboard. The interaction among the central process and the entities involves 20 data flows. The Authorized Driver supplies data through the Login Credentials, Registration Details, Profile Update, BLOWBAGETS Inspection, and Damage Report data flows, and receives data through the Assigned Vehicle Information and Notification data flows. The Agency Administrator supplies data through the Login Credentials, Account Detail, Maintenance Record, Dispatch Record, Damage Review, and Report Filter data flows, and exchanges data in both directions through the Vehicle Record and Driver Record data flows. In return, the system provides the Agency Administrator with the Driver Report, Monitoring Status, Dashboard Summary, Generated Report, and Notification data flows. The Notification data flow to the driver includes the push alerts delivered to the driver's mobile device, which are part of the system's notification delivery as shown in Figure 2. As shown in the diagram, all information moves in and out of one central system rather than through separate, disconnected records, while each Agency Administrator's access remains limited to the records of their own agency.

#### Data Flow Diagram

The Data Flow Diagram (DFD) of the Rescue Vehicle Management System illustrates the logical movement of data between external entities, system processes, and data stores. To maintain readability while clearly representing the operations of the two system users, the DFD is presented through two entity-oriented views: Authorized Driver Operations and Agency Administrator Operations. The diagrams use the Gane and Sarson notation and show how user inputs are processed, stored, retrieved, and returned as system outputs. Together, the two views represent the major internal data flows of the Rescue Vehicle Management System.

*(Note: The DFD is partitioned into Figure 4.1 for Authorized Driver Operations and Figure 4.2 for Agency Administrator Operations, decomposed into eight major processes: Authenticate and Manage Account (1), Manage Vehicle and Driver Record / Manage Fleet Record (2), Process Inspection / Manage Inspection (3), Manage Damage and Repair / Manage Vehicle Damage (4), Manage Preventive Maintenance (5), Manage Dispatch (6), Generate Dashboard and Report / Generate Report (7), and Send Notification (8), interacting with data stores D1 Users, D2 Vehicles, D3 Inspections, D4 Damage Reports, D5 Repair Logs, D6 PM Schedules, D7 Dispatches, and D8 Notifications.)*

#### Functional Decomposition Diagram

The Functional Decomposition Diagram provides a top-down view of the Rescue Vehicle Management System, breaking the system into progressively smaller functions in the form of an organization chart. Beginning with the complete system at the top, it is divided into its major functional areas, and each area into the specific operations it performs. This gives a clear, organized picture of what the system does and serves as a blueprint of the modules built during development. The Functional Decomposition Diagram is shown in Figure 5.

**Figure 5. Functional Decomposition Diagram of the Rescue Vehicle Management System**  
*(See FDD in manuscript.)*

Figure 5 presents the Functional Decomposition Diagram of the Rescue Vehicle Management System. At the top is the complete system, which is decomposed into four major functional areas: User Management, Vehicle Management, Maintenance Management, and Reporting and Notifications. User Management covers user login, driver registration and approval, administrator account creation, profile management, and password recovery. Vehicle Management covers vehicle and driver record management, assigned vehicle viewing, license and NC II expiry monitoring, dispatch management, and vehicle status monitoring. Maintenance Management covers inspection management, damage management, and preventive maintenance management, including inspection submission and review, inspection history, damage reporting and review, repair logging, and preventive maintenance scheduling and tracking. Reporting and Notifications covers the dashboard summary, report generation, and notification management. Each major functional area is further decomposed into specific operations that correspond to the functional requirements of the system, providing a clear and organized representation of the functions and serving as a blueprint for the development of the Rescue Vehicle Management System.

#### Entity-Relationship Diagram and Data Dictionary

##### Entity Relationship Diagram

An Entity Relationship Diagram (ERD) is a graphical representation of the entities in a database and the relationships among them. It is a tool used in database design to model real-world objects or concepts as entities and to show how those entities are connected through their attributes and keys. The entity relationship diagram of the Rescue Vehicle Management System is shown in Figure 6.

**Figure 6. Entity Relationship Diagram of the Rescue Vehicle Management System**  
*(See ERD in manuscript.)*

Figure 6 presents the Entity Relationship Diagram of the Rescue Vehicle Management System, which models the database structure that supports the system's functional requirements. The database is composed of eleven domain tables: agencies, users, vehicles, inspection_checklist_items, inspections, inspection_items, damage_reports, repair_logs, pm_schedules, dispatches, and notifications. The agencies table anchors the design — an agency has many users, vehicles, inspections, damage reports, repair logs, preventive maintenance schedules, dispatches, and notifications, while each of those records belongs to exactly one agency through its agency_id foreign key, enforcing the system's agency-scoped access. The users table stores both Agency Administrators and Authorized Drivers, distinguished by the role field; a driver may be the assigned driver of one or more vehicles while each vehicle has at most one primary driver (vehicles.assigned_driver_id), may also be the optional secondary driver of one or more vehicles (vehicles.secondary_driver_id), and a driver authors many inspections, damage reports, repair logs, and dispatches (driver_id), while an administrator is recorded as the reviewer of inspections and damage reports (reviewed_by). The vehicles table is the operational hub: each vehicle has many inspections, damage reports, repair logs, preventive maintenance schedules, and dispatches (vehicle_id), and holds the single operational status read and written across modules. An inspection is composed of several inspection_items, each corresponding to one entry in the inspection_checklist_items catalog, and each notification is addressed to a single user. All relationships are shown in crow's-foot notation, with the "one" side on the parent table and the "many" side on the child, so the diagram expresses both the data structure and the multiplicity of each relationship.

---

##### Data Dictionary

The Data Dictionary is a centralized document that gives detailed information about the structure, organization, and attributes of the data used in the Rescue Vehicle Management System. It includes clear definitions and descriptions of all the data inside the system, such as tables, fields, and data types. This dictionary is very important for data management because it helps everyone understand the data, maintain its quality, and communicate effectively among developers, agency administrators, and other stakeholders.

Table 5 shows the data dictionary for the agencies table of the Rescue Vehicle Management System.

**Table 5. Data Dictionary of agencies Table of the Rescue Vehicle Management System**

| Field Name | Data Type | Null | Description | Key | Reference Table |
|---|---|---|---|---|---|
| id | Int | No | Unique identifier for each agency. | PK | — |
| code | Varchar(10) | No | Short agency code: BFP, PNP, CDRRMO, or CHO. | — | — |
| name | Varchar(255) | No | Full name of the agency. | — | — |
| location | Varchar(255) | Yes | Office location of the agency. | — | — |
| contact_number | Varchar(50) | Yes | Contact number of the agency. | — | — |
| email | Varchar(255) | Yes | Contact email address of the agency. | — | — |
| logo_path | Varchar(255) | Yes | File path of the agency logo shown on the dashboard. | — | — |
| license_expiry_warning_days | Smallint | No | Number of days before expiry at which a driver license is flagged as expiring soon. Configurable per agency. | — | — |
| created_at | Datetime | Yes | Date and time the record was created. | — | — |
| updated_at | Datetime | Yes | Date and time the record was last updated. | — | — |

Table 5 – The agencies table stores the profile of each of the four participating agencies — the Bureau of Fire Protection, the Philippine National Police, the City Disaster Risk Reduction and Management Office, and the City Health Office. It holds the agency code, name, location, contact details, and logo, together with the number of days before expiry at which a driver license is flagged as expiring soon. That threshold is stored here rather than fixed in the program so that each agency may set its own warning period. Each record represents one agency and serves as the reference that separates every other record in the system by agency.

Table 6 shows the data dictionary for the users table of the Rescue Vehicle Management System.

**Table 6. Data Dictionary of users Table of the Rescue Vehicle Management System**

| Field Name | Data Type | Null | Description | Key | Reference Table |
|---|---|---|---|---|---|
| id | Int | No | Unique identifier for each user account. | PK | — |
| agency_id | Int | No | Agency the user belongs to. | FK | agencies |
| role | Enum | No | Role of the user: admin or driver. | — | — |
| name | Varchar(255) | No | Full name of the Agency Administrator or Authorized Driver. | — | — |
| email | Varchar(255) | No | Email address used to sign in. Unique across the system. | — | — |
| password | Varchar(255) | No | Hashed password of the account. | — | — |
| status | Enum | No | Account state: pending, active, or rejected. | — | — |
| license_number | Varchar(50) | Yes | Driver license number. Empty for Agency Administrators. | — | — |
| license_expiry_date | Date | Yes | Expiry date of the driver license. Empty for Agency Administrators. | — | — |
| nc_ii_number | Varchar(50) | Yes | TESDA National Certificate II (NC II, Driving) number of the driver. Optional; empty for Agency Administrators and for drivers who do not hold an NC II. | — | — |
| nc_ii_expiry_date | Date | Yes | Expiry date of the driver's NC II certificate. Monitored against the agency configurable warning window. Optional; empty for Agency Administrators and drivers without an NC II. | — | — |
| fcm_token | Varchar(255) | Yes | Device token used to deliver push notifications to the mobile application. | — | — |
| email_verified_at | Datetime | Yes | Date and time the email address was verified. | — | — |
| remember_token | Varchar(100) | Yes | Token used to keep a web session signed in. | — | — |
| created_at | Datetime | Yes | Date and time the record was created. | — | — |
| updated_at | Datetime | Yes | Date and time the record was last updated. | — | — |

Table 6 – The users table stores every account in the system, both Agency Administrators and Authorized Drivers, distinguished by the role field. Administrators and drivers are kept in one table because both must sign in, and a driver record and a login account are always the same person. The license number and license expiry date are filled in for drivers and left empty for administrators, and the device token is used to deliver push notifications to the mobile application. The status field records whether a self-registered driver is still pending approval, active, or rejected. Each record represents one person who can sign in to the system.

Table 7 shows the data dictionary for the vehicles table of the Rescue Vehicle Management System.

**Table 7. Data Dictionary of vehicles Table of the Rescue Vehicle Management System**

| Field Name | Data Type | Null | Description | Key | Reference Table |
|---|---|---|---|---|---|
| id | Int | No | Unique identifier for each vehicle. | PK | — |
| agency_id | Int | No | Agency that owns the vehicle. | FK | agencies |
| assigned_driver_id | Int | Yes | Primary Authorized Driver assigned to the vehicle. | FK | users |
| secondary_driver_id | Int | Yes | Optional secondary or backup Authorized Driver crewing the vehicle. Must be different from the primary driver. Empty for a single-driver vehicle. | FK | users |
| type | Varchar(100) | No | Type of vehicle, selected from presets (e.g., Fire Truck, Ambulance, Patrol Car) or custom-specified via an "Other" option. | — | — |
| plate_number | Varchar(20) | No | Plate number of the vehicle. Unique within the agency. | — | — |
| make | Varchar(100) | No | Manufacturer of the vehicle. | — | — |
| model | Varchar(100) | No | Model of the vehicle. | — | — |
| engine_number | Varchar(50) | Yes | Engine number of the vehicle. Unique within the agency. | — | — |
| chassis_number | Varchar(50) | Yes | Chassis number of the vehicle. Unique within the agency. | — | — |
| current_mileage | Int | No | Current odometer reading in kilometers. Used for mileage-based preventive maintenance. | — | — |
| status | Enum | No | Single shared operational status: Operational, Dispatched, Not Operational, or Under Preventive Maintenance. | — | — |
| created_at | Datetime | Yes | Date and time the record was created. | — | — |
| updated_at | Datetime | Yes | Date and time the record was last updated. | — | — |

Table 7 – The vehicles table stores the fleet of each agency, including the vehicle type (supporting standard emergency presets or custom specification when "Other" is selected), plate number, make, model, engine number, chassis number, and current odometer reading. It also carries the single shared operational status of the vehicle — Operational, Dispatched, Not Operational, or Under Preventive Maintenance — which every module reads and writes so that all screens report the same condition. The assigned driver field names the primary Authorized Driver of the vehicle. Each record represents one physical vehicle belonging to one agency.

Table 8 shows the data dictionary for the inspection_checklist_items table of the Rescue Vehicle Management System.

**Table 8. Data Dictionary of inspection_checklist_items Table of the Rescue Vehicle Management System**

| Field Name | Data Type | Null | Description | Key | Reference Table |
|---|---|---|---|---|---|
| id | Int | No | Unique identifier for each checklist item. | PK | — |
| name | Varchar(100) | No | Name of the checklist item, such as Battery, Brakes, or Fire Pump. | — | — |
| is_bfp_only | Boolean | No | Marks the two items that apply only to BFP vehicles: Hydraulic System and Fire Pump. | — | — |
| sort_order | Smallint | No | Order in which the item appears on the checklist. | — | — |
| created_at | Datetime | Yes | Date and time the record was created. | — | — |
| updated_at | Datetime | Yes | Date and time the record was last updated. | — | — |

Table 8 – The inspection checklist items table stores the standard BLOWBAGETS checklist used for daily inspections. It contains the twelve standard items — Battery, Lights, Oil, Water, Brakes, Air, Gas, Engine, Tires, Power Steering, Horn/Siren, and Directional Signals — and the two additional items that apply only to BFP vehicles, Hydraulic System and Fire Pump. This is a reference list shared by all four agencies rather than an operational record, so it is not scoped to any single agency. Each record represents one item that a driver assesses during an inspection.

Table 9 shows the data dictionary for the inspections table of the Rescue Vehicle Management System.

**Table 9. Data Dictionary of inspections Table of the Rescue Vehicle Management System**

| Field Name | Data Type | Null | Description | Key | Reference Table |
|---|---|---|---|---|---|
| id | Int | No | Unique identifier for each inspection. | PK | — |
| agency_id | Int | No | Agency that owns the inspection record. | FK | agencies |
| vehicle_id | Int | No | Vehicle that was inspected. | FK | vehicles |
| driver_id | Int | No | Authorized Driver who performed the inspection. | FK | users |
| inspection_date | Date | No | The date of daily inspection was performed. | — | — |
| review_status | Enum | No | Review state of the inspection: Pending or Reviewed. | — | — |
| reviewed_by | Int | Yes | Agency Administrator who reviewed the inspection. | FK | users |
| reviewed_at | Datetime | Yes | Date and time the inspection was reviewed. | — | — |
| created_at | Datetime | Yes | Date and time the inspection was submitted. | — | — |
| updated_at | Datetime | Yes | Date and time the record was last updated. | — | — |

Table 9 – The inspections table stores each daily BLOWBAGETS inspection submitted by an Authorized Driver from the mobile application. It records the vehicle inspected, the driver who performed it, the date of the inspection, and the review state, which begins as Pending and becomes Reviewed once an Agency Administrator has assessed it. When the inspection is reviewed, the administrator who reviewed it and the date and time of the review are also recorded. Each record represents one complete inspection of one vehicle on one occasion.

Table 10 shows the data dictionary for the inspection_items table of the Rescue Vehicle Management System.

**Table 10. Data Dictionary of inspection_items Table of the Rescue Vehicle Management System**

| Field Name | Data Type | Null | Description | Key | Reference Table |
|---|---|---|---|---|---|
| id | Int | No | Unique identifier for each inspection item result. | PK | — |
| inspection_id | Int | No | Inspection this result belongs to. | FK | inspections |
| checklist_item_id | Int | No | Checklist item that was assessed. | FK | inspection_checklist_items |
| status | Enum | No | Result for the item: OK or Has Issue. | — | — |
| remarks | Text | Yes | Explanation of the fault. Required when the status is Has Issue. | — | — |

Table 10 – The inspection items table stores the individual results of an inspection. Each row links one inspection to one checklist item and records whether that item was found to be OK or to have an issue, together with the driver's remarks. Remarks are required whenever an item is flagged as having an issue, so that a reported fault is always explained. Each record represents the result of a single checklist item within a single inspection.

Table 11 shows the data dictionary for the damage_reports table of the Rescue Vehicle Management System.

**Table 11. Data Dictionary of damage_reports Table of the Rescue Vehicle Management System**

| Field Name | Data Type | Null | Description | Key | Reference Table |
|---|---|---|---|---|---|
| id | Int | No | Unique identifier for each damage report. | PK | — |
| agency_id | Int | No | Agency that owns the damage report. | FK | agencies |
| vehicle_id | Int | No | Vehicle that was damaged. | FK | vehicles |
| driver_id | Int | No | Authorized Driver who submitted the report. | FK | users |
| nature_of_damage | Text | No | Description of the damage. | — | — |
| suspected_parts | Varchar(255) | Yes | Parts suspected to be defective. | — | — |
| photo_path | Varchar(255) | Yes | File path of the optional photo attachment. | — | — |
| date_reported | Date | No | Date the report was submitted. Set automatically. | — | — |
| status | Enum | No | Review state of the report: Pending or Reviewed. | — | — |
| reviewed_by | Int | Yes | Agency Administrator who assessed the report. | FK | users |
| reviewed_at | Datetime | Yes | Date and time the report was assessed. | — | — |
| created_at | Datetime | Yes | Date and time the record was created. | — | — |
| updated_at | Datetime | Yes | Date and time the record was last updated. | — | — |

Table 11 – The damage reports table stores the standardized damage reports submitted by Authorized Drivers. It records the vehicle concerned, the driver who filed the report, a description of the nature of the damage, the parts suspected to be defective, and an optional photo attachment. The date reported is set automatically on submission, and the status begins as Pending until an Agency Administrator assesses the report and marks it Reviewed. Each record represents one reported instance of damage to one vehicle.

Table 12 shows the data dictionary for the repair_logs table of the Rescue Vehicle Management System.

**Table 12. Data Dictionary of repair_logs Table of the Rescue Vehicle Management System**

| Field Name | Data Type | Null | Description | Key | Reference Table |
|---|---|---|---|---|---|
| id | Int | No | Unique identifier for each repair record. | PK | — |
| agency_id | Int | No | Agency that owns the repair record. | FK | agencies |
| vehicle_id | Int | No | Vehicle that was repaired. | FK | vehicles |
| driver_id | Int | Yes | Authorized Driver assigned to the vehicle at the time of repair. | FK | users |
| repair_date | Date | No | Date the repair was performed or logged. | — | — |
| scope_of_work | Text | No | Description of the work performed. | — | — |
| parts_replaced | Text | Yes | Parts replaced during the repair. | — | — |
| cost | Decimal(10,2) | Yes | Cost of the repair. | — | — |
| repair_source | Enum | No | Who performed the repair: Internal Office, GSO Motorpool, or External Repair Shop. | — | — |
| external_shop_name | Varchar(255) | Yes | Name of the shop when the repair source is External Repair Shop. | — | — |
| remarks | Text | Yes | Additional notes about the repair. | — | — |
| receipt_path | Varchar(255) | Yes | Path to the receipt or service document issued by the external repair shop (or job order from GSO Motorpool). Required when repair source is External Repair Shop. | — | — |
| created_at | Datetime | Yes | Date and time the record was created. | — | — |
| updated_at | Datetime | Yes | Date and time the record was last updated. | — | — |

Table 12 – The repair logs table stores the repair activities carried out on each vehicle. It records the date of the repair, the scope of work performed, the parts replaced, the optional cost, and the source of the repair — Internal Office, GSO Motorpool, or External Repair Shop — with the name of the shop recorded when the repair was performed externally. The driver assigned to the vehicle at the time may also be recorded. Each record represents one repair activity performed on one vehicle.

Table 13 shows the data dictionary for the pm_schedules table of the Rescue Vehicle Management System.

**Table 13. Data Dictionary of pm_schedules Table of the Rescue Vehicle Management System**

| Field Name | Data Type | Null | Description | Key | Reference Table |
|---|---|---|---|---|---|
| id | Int | No | Unique identifier for each preventive maintenance schedule. | PK | — |
| agency_id | Int | No | Agency that owns the schedule. | FK | agencies |
| vehicle_id | Int | No | Vehicle the schedule applies to. | FK | vehicles |
| service_target | Varchar(255) | No | Part or service to be maintained, such as Oil Change and Filter. | — | — |
| schedule_document_path | Varchar(255) | Yes | Path to the optional supporting document attached when the schedule is created, such as a pre-inspection checklist. Separate from the completion document. | — | — |
| pm_type | Enum | No | Basis of the schedule: Mileage-Based or Time-Based. | — | — |
| interval_km | Int | Yes | Distance in kilometers between services. Mileage-based schedules only. | — | — |
| last_pm_mileage | Int | Yes | Odometer reading at the last service. Mileage-based schedules only. | — | — |
| due_mileage | Int | Yes | Odometer reading at which the service becomes due. | — | — |
| due_date | Date | Yes | Date on which the service becomes due. Time-based schedules only. | — | — |
| due_soon_threshold_km | Int | Yes | Kilometers before the due mileage at which the schedule is flagged Due Soon. Configurable. | — | — |
| due_soon_threshold_days | Smallint | Yes | Days before the due date at which the schedule is flagged Due Soon. Configurable. | — | — |
| status | Enum | No | State of the schedule: Upcoming, Due Soon, Due, or Completed. | — | — |
| date_serviced | Date | Yes | Date the service was carried out. | — | — |
| completion_mileage | Int | Yes | Odometer reading at the time of service. Mileage-based schedules only. | — | — |
| completion_repair_source | Enum | Yes | Who carried out the service: Internal Office, GSO Motorpool, or External Repair Shop. | — | — |
| completion_external_shop_name | Varchar(255) | Yes | Name of the shop when the completion source is External Repair Shop. | — | — |
| completion_parts_replaced | Text | Yes | Parts replaced during the service. | — | — |
| completion_remarks | Text | Yes | Notes and findings recorded on completion. | — | — |
| completion_receipt_path | Varchar(255) | Yes | Path to the receipt or service document issued by the external repair shop when preventive maintenance was completed externally. | — | — |
| created_at | Datetime | Yes | Date and time the record was created. | — | — |
| updated_at | Datetime | Yes | Date and time the record was last updated. | — | — |

Table 13 – The preventive maintenance schedules table stores the maintenance plans set for each vehicle. A schedule is either mileage-based, using a kilometre interval and the odometer reading at the last service, or time-based, using a due date. Each schedule carries its own Due Soon threshold, set by the Agency Administrator, so that the warning period may differ from one service to another. The status moves through Upcoming, Due Soon, Due, and Completed, and the completion fields record the date serviced, the odometer reading at the time of service for mileage-based schedules, the source of the service, the shop where it was performed externally, the supporting document issued for the service, the parts replaced, and any remarks. Each record represents one maintenance cycle for one service target on one vehicle.

Table 14 shows the data dictionary for the dispatches table of the Rescue Vehicle Management System.

**Table 14. Data Dictionary of dispatches Table of the Rescue Vehicle Management System**

| Field Name | Data Type | Null | Description | Key | Reference Table |
|---|---|---|---|---|---|
| id | Int | No | Unique identifier for each dispatch record. | PK | — |
| agency_id | Int | No | Agency that owns the dispatch record. | FK | agencies |
| vehicle_id | Int | No | Vehicle that was dispatched. | FK | vehicles |
| driver_id | Int | No | Authorized Driver who was dispatched. | FK | users |
| second_driver_id | Int | Yes | Optional second Authorized Driver for a mission crewed by two drivers. Must be different from the primary dispatched driver. Empty for an ordinary one-driver dispatch. | FK | users |
| mission_type | Enum | No | Type of mission: Fire Response, Medical Response, Rescue Operation, Patrol, Administrative Travel, or Others. | — | — |
| mission_other | Varchar(255) | Yes | Description of the mission when the mission type is Others. | — | — |
| location | Varchar(255) | No | Location the vehicle was dispatched to. | — | — |
| time_out | Datetime | No | Date and time the vehicle left. Sets the vehicle status to Dispatched. | — | — |
| travel_order_path | Varchar(255) | Yes | Path to the optional travel order or trip authorization attached when the dispatch is opened. Optional so an emergency response is never delayed. | — | — |
| odometer_out | Int | Yes | Odometer reading recorded at time out. | — | — |
| time_in | Datetime | Yes | Date and time the vehicle returned. Empty while the dispatch is active. | — | — |
| odometer_in | Int | Yes | Odometer reading recorded at time in. Updates the vehicle current mileage when higher. | — | — |
| return_status | Enum | Yes | Status set on return: Operational, Not Operational, or Under Preventive Maintenance. | — | — |
| remarks | Text | Yes | Notes recorded when the dispatch was closed. | — | — |
| created_at | Datetime | Yes | Date and time the record was created. | — | — |
| updated_at | Datetime | Yes | Date and time the record was last updated. | — | — |

Table 14 – The dispatches table stores the record of each vehicle deployment. It records the vehicle and driver dispatched, the mission type and location, and the date and time out, with an optional odometer reading taken as the vehicle leaves. Opening a dispatch automatically sets the vehicle status to Dispatched. When the dispatch is closed, the date and time in, an optional odometer reading on arrival, the return status of the vehicle, and any remarks are recorded; a time-in reading higher than the vehicle's current mileage updates that mileage. A dispatch with no time in is still active. Each record represents one deployment of one vehicle on one mission.

Table 15 shows the data dictionary for the notifications table of the Rescue Vehicle Management System.

**Table 15. Data Dictionary of notifications Table of the Rescue Vehicle Management System**

| Field Name | Data Type | Null | Description | Key | Reference Table |
|---|---|---|---|---|---|
| id | Int | No | Unique identifier for each notification. | PK | — |
| agency_id | Int | No | Agency the notification belongs to. | FK | agencies |
| user_id | Int | No | User who receives the notification. | FK | users |
| type | Enum | No | Category of the alert, such as New Damage Report, Inspection Flagged, License Expiring, NC II Expiring, NC II Expired, PM Due, New Administrator, or Password Reset. | — | — |
| title | Varchar(255) | No | Short headline of the notification. | — | — |
| message | Text | No | Body text of the notification. | — | — |
| data | JSON | Yes | Reference details such as the plate number or the record concerned. | — | — |
| is_read | Boolean | No | Marks whether the recipient has opened the notification. | — | — |
| read_at | Datetime | Yes | Date and time the notification was opened. | — | — |
| created_at | Datetime | Yes | Date and time the record was created. | — | — |
| updated_at | Datetime | Yes | Date and time the record was last updated. | — | — |

Table 15 – The notifications table stores the alerts raised by the system for both Authorized Drivers and Agency Administrators. It records the recipient, the category of the alert, the headline and body text, and a reference payload identifying the record the alert concerns. The read flag and the date and time read allow an Agency Administrator to keep their own notification list manageable as alerts accumulate. Each record represents one alert addressed to one user.

---

# CHAPTER 5: RECOMMENDATIONS

After conducting the study and successfully developing the Responsive Vehicle Management System (RVMS) for the emergency response agencies of Calbayog City (CDRRMO, BFP, CHO, and PNP), the researchers focused on the primary objectives of centralizing vehicle inventory, digitizing daily BLOWBAGETS inspection logs, scheduling preventive maintenance, and recording dispatch operations. The developed web and mobile platforms provide an efficient, centralized operational workflow that eliminates paper-based record-keeping and delays in identifying vehicle maintenance needs. Nevertheless, based on practical field feedback from agency administrators, designated drivers, and the capstone project panel, the researchers recognize opportunities for further functional and technical enhancements, and thus present several key recommendations for future system development.

To enhance day-to-day operational management, dispatch safety, and maintenance coordination, the researchers recommend refining the system's operational status classifications and reporting features. First, the system should expand its vehicle availability classifications by introducing intermediate operational states such as "Partially Operational" (or "Operational with Limitations"). In emergency fleet operations, vehicles frequently encounter non-critical defects—such as faulty auxiliary lights, malfunctioning sirens, or minor body damage—that do not completely immobilize the vehicle but make it unsafe for high-speed emergency missions. An intermediate status would allow dispatchers to allocate these vehicles exclusively to routine administrative trips or low-priority transport while preventing their deployment to critical emergency responses until full repairs are completed. Second, the damage reporting module should be upgraded to incorporate a standardized damage severity classification system (e.g., minor cosmetic damage, moderate operational wear, or severe/immobilizing defects) accompanied by structured damage rubrics and multi-angle photographic evidence to assist motorpool supervisors in prioritizing repair work orders objectively. Third, the system should integrate an interactive, two-way preventive maintenance (PM) scheduling and confirmation mechanism—such as an automated conversational chatbot or dedicated in-app acknowledgement module—allowing drivers and fleet administrators to negotiate, confirm, or request rescheduling for maintenance appointments. Furthermore, the researchers suggest incorporating a dedicated dispatch status messaging feature, enabling drivers to transmit real-time arrival confirmations (e.g., a one-tap notification indicating that the vehicle has safely arrived at the designated emergency scene or dispatch location) to enhance situational awareness at the central command post.

To ensure driver safety, compliance, and real-time operational reliability in high-intensity field environments, the researchers recommend upgrading the mobile notification system and exploring hardware telematics integration. Specifically, the mobile application should implement persistent, high-priority alert mechanisms—utilizing continuous vibration patterns and distinct audible alarm frequencies—for vehicles with overdue preventive maintenance or unresolved critical defects, ensuring that urgent safety warnings are not overlooked by drivers operating in noisy conditions. Additionally, while the current system captures odometer readings upon dispatch arrival, future versions should transition toward automated Internet of Things (IoT) hardware integration by incorporating On-Board Diagnostics (OBD-II) telematics and GPS modules. This hardware integration would automate live mileage collection, fuel consumption monitoring, real-time location tracking, and continuous engine diagnostic fault code retrieval directly from the vehicle’s electronic control unit.

Finally, the researchers recommend the institutionalization of the RVMS by the City Government of Calbayog across all local emergency response departments. Integrating the platform into the standard operating procedures (SOPs) of the CDRRMO, BFP, CHO, and PNP will guarantee data integrity, uniform preventive maintenance schedules, and inter-agency resource coordination during city-wide emergencies. For future researchers, the study suggests investigating machine learning algorithms for predictive maintenance, analyzing historical repair logs and mileage usage patterns to forecast component failures before breakdowns occur. Future developers are also encouraged to maintain close collaboration with fleet personnel during system prototyping, as practical operational conditions frequently yield valuable insights that guide effective software refinements.

