/* ==========================================================================
   RVMS Admin — per-agency demo data layer
   Static prototype: each agency sees only its own records (plan §6.1, §6.12).
   The active agency comes from ?agency= (set by the login chips) and is
   remembered in localStorage so it persists across page navigation.
   ========================================================================== */
(function () {
  "use strict";

  /* ----------------------------- helpers ------------------------------- */
  const STATUS_BADGE = {
    "Operational": "badge-operational",
    "Dispatched": "badge-dispatched",
    "Under PM": "badge-pm",
    "Not Operational": "badge-not-operational"
  };
  // Full vehicle-status display label — matches the driver mobile app wording.
  const STATUS_LABEL = { "Under PM": "Under Preventive Maintenance" };
  const statusLabel = s => STATUS_LABEL[s] || s;
  // Shows the named external shop when the repair source is an external shop.
  const repairSourceLabel = r => (r.source === "External Repair Shop" && r.shop)
    ? r.source + " — " + r.shop : r.source;
  // Shows the admin-specified detail when the dispatch mission type is "Others".
  const missionLabel = d => (d.mission === "Others" && d.missionOther)
    ? d.mission + " — " + d.missionOther : d.mission;
  const LIC = { "Valid": "success", "Expiring Soon": "warning", "Expired": "danger" };
  // Review status colors mirror the mobile app: Pending = slate, Reviewed = navy.
  const REVIEW_BADGE = { "Pending": "badge-pending", "Reviewed": "badge-reviewed" };
  const PM_BADGE = {
    "Due": "bg-danger", "Due Soon": "bg-warning text-dark",
    "Upcoming": "badge-upcoming", "Completed": "bg-secondary"
  };
  const NOTIF = {
    damage:     { icon: "bi-exclamation-triangle", tone: "danger",  title: "New Damage Report Submitted", link: "inspections-damage.html" },
    inspection: { icon: "bi-clipboard-check",      tone: "primary", title: "Daily Inspection Submitted",   link: "inspections-damage.html" },
    license:    { icon: "bi-person-badge",         tone: "warning", title: "License Alert",                link: "drivers.html" },
    pm:         { icon: "bi-wrench-adjustable",    tone: "warning", title: "Preventive Maintenance",       link: "pm.html" }
  };
  const esc = s => String(s).replace(/&/g, "&amp;").replace(/</g, "&lt;").replace(/>/g, "&gt;");
  const expiryClass = s => s === "Expiring Soon" ? "text-warning fw-bold" : s === "Expired" ? "text-danger fw-bold" : "";

  /* ------------------------------ data --------------------------------- */
  const STANDARD_BLOWBAGETS = [
    "Battery", "Lights", "Oil", "Water", "Brakes", "Air",
    "Gas", "Engine", "Tires", "Power Steering", "Horn/Siren", "Directional Signals"
  ];

  const AGENCIES = {
    BFP: {
      name: "Bureau of Fire Protection", short: "BFP", logo: "../assets/img/agency/bfp-logo.jpg",
      location: "Calbayog City", contact: "(055) 123-4567", domain: "bfp.gov.ph", avatar: "A",
      extraItems: ["Hydraulic System", "Fire Pump"], primaryMission: "Fire Response",
      frequentIssues: [
        { issue: "Brakes — noise / pad wear", count: 5, last: "May 28, 2026", systemTag: "Brakes" },
        { issue: "Tires — low pressure / wear", count: 4, last: "Today", systemTag: "Tires" },
        { issue: "Lights — alignment / busted bulb", count: 3, last: "May 16, 2026", systemTag: "Lights" },
        { issue: "Cooling / aircon performance", count: 2, last: "May 22, 2026", systemTag: "Cooling" }
      ],
      vehicles: [
        { plate: "ABC-1234", type: "Fire Truck", mm: "Isuzu FTR 850", driver: "Juan Dela Cruz", km: "45,230 km", status: "Operational", engine: "4HK1-TC-587234", chassis: "JALC4W14697100345" },
        { plate: "BCD-2310", type: "Fire Truck", mm: "Hino 500", driver: "Ricardo Bautista", km: "38,420 km", status: "Dispatched", engine: "J08E-WD-441203", chassis: "JHDFG8JL5GX128466" },
        { plate: "CDE-3421", type: "Rescue Van", mm: "Toyota Hiace", driver: "Allan Reyes", km: "51,780 km", status: "Operational", engine: "2KD-7896543", chassis: "JTFSS22P5G0123456" },
        { plate: "EFG-4532", type: "Water Tanker", mm: "Isuzu FVR", driver: "Carlos Mendoza", km: "81,650 km", status: "Under PM", engine: "6HK1-XS-778812", chassis: "JALFVR34LC7000891" },
        { plate: "FGH-5643", type: "Service Vehicle", mm: "Mitsubishi L300", driver: "Ramon Cruz", km: "96,300 km", status: "Not Operational", engine: "4D56-CC-884109", chassis: "MMBJNKA40CD034567" },
        { plate: "GHI-6754", type: "Ambulance", mm: "Nissan Urvan", driver: "Felipe Ramos", km: "64,120 km", status: "Operational", engine: "ZD30-DD-445566", chassis: "JN1TG4E25Z0067432" }
      ],
      drivers: [
        { name: "Juan Dela Cruz", license: "N01-12-345678", expiry: "Dec 15, 2027", status: "Valid", vehicle: "ABC-1234 (Fire Truck)" },
        { name: "Ricardo Bautista", license: "N01-14-220815", expiry: "Jul 8, 2026", status: "Expiring Soon", vehicle: "BCD-2310 (Fire Truck)" },
        { name: "Allan Reyes", license: "N01-16-334455", expiry: "Sep 21, 2028", status: "Valid", vehicle: "CDE-3421 (Rescue Van)" },
        { name: "Carlos Mendoza", license: "N01-11-556677", expiry: "Feb 14, 2028", status: "Valid", vehicle: "EFG-4532 (Water Tanker)" },
        { name: "Ramon Cruz", license: "N01-09-778899", expiry: "May 28, 2026", status: "Expired", vehicle: "FGH-5643 (Service Vehicle)" },
        { name: "Felipe Ramos", license: "N01-13-990011", expiry: "Nov 3, 2027", status: "Valid", vehicle: "GHI-6754 (Ambulance)" }
      ],
      inspections: [
        { date: "Today", time: "07:30 AM", plate: "ABC-1234", driver: "Juan Dela Cruz", result: "All OK", remarks: "None", review: "Pending" },
        { date: "Today", time: "07:05 AM", plate: "BCD-2310", driver: "Ricardo Bautista", result: "Has Issue", remarks: "Low tire pressure (front right)", review: "Pending" },
        { date: "Today", time: "06:50 AM", plate: "GHI-6754", driver: "Felipe Ramos", result: "All OK", remarks: "None", review: "Reviewed" },
        { date: "Yesterday", time: "07:15 AM", plate: "ABC-1234", driver: "Juan Dela Cruz", result: "Has Issue", remarks: "Brakes — unusual noise during braking", review: "Reviewed" }
      ],
      damage: [
        { date: "Today", time: "08:10 AM", plate: "ABC-1234", driver: "Juan Dela Cruz", nature: "Cracked side mirror (driver side)", parts: "Side mirror assembly", attachment: true, review: "Pending" },
        { date: "Yesterday", time: "04:45 PM", plate: "FGH-5643", driver: "Ramon Cruz", nature: "Transmission slipping — unsafe to deploy", parts: "Transmission", attachment: false, review: "Pending" },
        { date: "May 28, 2026", time: "09:20 AM", plate: "ABC-1234", driver: "Juan Dela Cruz", nature: "Brake pad wear — unusual noise during braking", parts: "Front brake pads", attachment: false, review: "Reviewed" }
      ],
      pmActive: [
        { plate: "EFG-4532", vtype: "Water Tanker", target: "Engine Service", schedType: "Mileage-Based", dueMain: "81,000 km", dueSub: "Current: 81,650 km — overdue", status: "Due" },
        { plate: "ABC-1234", vtype: "Fire Truck", target: "Oil Change & Filter", schedType: "Mileage-Based", dueMain: "46,000 km", dueSub: "Current: 45,230 km", status: "Due Soon" },
        { plate: "BCD-2310", vtype: "Fire Truck", target: "Brake Fluid Flush", schedType: "Time-Based", dueMain: "Dec 10, 2026", dueSub: "6 months away", status: "Upcoming" }
      ],
      pmCompleted: [
        { plate: "GHI-6754", vtype: "Ambulance", performed: "Brake Inspection & Fluid", date: "May 20, 2026", source: "GSO Motorpool", parts: "Brake fluid" }
      ],
      dispatch: [
        { mission: "Fire Response", location: "Brgy. Obrero, Calbayog", plate: "BCD-2310", driver: "Ricardo Bautista", out: ["Today", "09:40 AM"], in: null, status: "Active" },
        { mission: "Fire Response", location: "Brgy. Aguit-itan, Calbayog", plate: "ABC-1234", driver: "Juan Dela Cruz", out: ["Jun 5, 2026", "02:10 PM"], in: ["Jun 5, 2026", "05:35 PM"], status: "Completed" },
        { mission: "Medical Response", location: "Brgy. Carayman, Calbayog", plate: "GHI-6754", driver: "Felipe Ramos", out: ["Jun 3, 2026", "09:00 AM"], in: ["Jun 3, 2026", "11:20 AM"], status: "Completed" },
        { mission: "Others", missionOther: "Fire Safety Awareness Parade", location: "Calbayog City Plaza", plate: "CDE-3421", driver: "Allan Reyes", out: ["Jun 2, 2026", "01:00 PM"], in: ["Jun 2, 2026", "04:00 PM"], status: "Completed" }
      ],
      repairs: [
        { date: "Jun 6, 2026", plate: "FGH-5643", driver: "Ramon Cruz", scope: "Transmission assessment and teardown", parts: "None (assessment only)", cost: "—", source: "External Repair Shop", shop: "Calbayog Diesel & Truck Works", remarks: "Overhaul required; vehicle remains Not Operational", vstatus: "Not Operational" },
        { date: "May 30, 2026", plate: "ABC-1234", driver: "Juan Dela Cruz", scope: "Front brake pad replacement", parts: "Brake pads (front set)", cost: "₱4,800", source: "Internal Office", remarks: "From damage report dated May 28; resolved", vstatus: "Operational" },
        { date: "May 22, 2026", plate: "GHI-6754", driver: "Felipe Ramos", scope: "Aircon compressor belt replacement", parts: "Compressor belt", cost: "₱2,500", source: "GSO Motorpool", remarks: "Cooling restored after medical transport run", vstatus: "Operational" },
        { date: "May 16, 2026", plate: "ABC-1234", driver: "Juan Dela Cruz", scope: "Headlight bulb replacement (left)", parts: "H4 halogen bulb", cost: "₱350", source: "Internal Office", remarks: "From damage report dated May 15; resolved", vstatus: "Operational" }
      ],
      notifications: [
        { kind: "damage", detail: "ABC-1234 — Cracked side mirror (driver side).", time: "8:10 AM", group: "Today" },
        { kind: "inspection", detail: "BCD-2310 — 1 item flagged: low tire pressure.", time: "7:05 AM", group: "Today" },
        { kind: "inspection", detail: "GHI-6754 — All items OK.", time: "6:50 AM", group: "Today" },
        { kind: "license", detail: "Ricardo Bautista — license expires Jul 8, 2026.", time: "9:00 AM", group: "Yesterday", tone: "warning", title: "License Expiring Soon" },
        { kind: "pm", detail: "EFG-4532 — engine service has reached its due mileage.", time: "8:00 AM", group: "Yesterday", title: "PM Due" },
        { kind: "license", detail: "Ramon Cruz — license expired on May 28, 2026.", time: "May 28", group: "Earlier", tone: "danger", title: "License Expired" }
      ]
    },

    PNP: {
      name: "Philippine National Police", short: "PNP", logo: "../assets/img/agency/pnp-logo.jpg",
      location: "Calbayog City", contact: "(055) 209-1175", domain: "pnp.gov.ph", avatar: "A",
      extraItems: [], primaryMission: "Patrol",
      frequentIssues: [
        { issue: "Tires — pressure / tread wear", count: 5, last: "Today", systemTag: "Tires" },
        { issue: "Lights — headlight alignment", count: 3, last: "Yesterday", systemTag: "Lights" },
        { issue: "Drive chain slack (motorcycles)", count: 3, last: "Today", systemTag: "Drivetrain" },
        { issue: "Brakes — response / pad wear", count: 2, last: "May 21, 2026", systemTag: "Brakes" }
      ],
      vehicles: [
        { plate: "PNP-1021", type: "Patrol Car", mm: "Toyota Vios", driver: "Eduardo Lim", km: "72,300 km", status: "Operational", engine: "2NR-FE-118723", chassis: "MR053KKB401029384" },
        { plate: "PNP-1045", type: "Patrol SUV", mm: "Mitsubishi Montero", driver: "Roberto Salazar", km: "88,410 km", status: "Dispatched", engine: "4N15-CB-552310", chassis: "MMBJYKL10KH041527" },
        { plate: "PNP-1067", type: "Mobile Patrol", mm: "Toyota Hilux", driver: "Manuel Tan", km: "56,900 km", status: "Operational", engine: "2GD-FTV-330145", chassis: "MR0HA8CD200148273" },
        { plate: "PNP-2210", type: "Patrol Motorcycle", mm: "Honda XR150", driver: "Andres Villamor", km: "21,450 km", status: "Operational", engine: "KD05E-220817", chassis: "MLHKD0540J5009132" },
        { plate: "PNP-2234", type: "Patrol Motorcycle", mm: "Yamaha Sniper 155", driver: "Jose Aquino", km: "33,780 km", status: "Under PM", engine: "G3K4E-771204", chassis: "MH3SG6920LK033410" },
        { plate: "PNP-1088", type: "Personnel Carrier", mm: "Isuzu Crosswind", driver: "Pedro Castillo", km: "102,560 km", status: "Not Operational", engine: "4JA1-CC-990217", chassis: "MPATFS85H7H512033" }
      ],
      drivers: [
        { name: "Eduardo Lim", license: "N02-15-101122", expiry: "Mar 12, 2028", status: "Valid", vehicle: "PNP-1021 (Patrol Car)" },
        { name: "Roberto Salazar", license: "N02-13-203344", expiry: "Jun 30, 2026", status: "Expiring Soon", vehicle: "PNP-1045 (Patrol SUV)" },
        { name: "Manuel Tan", license: "N02-16-405566", expiry: "Oct 5, 2027", status: "Valid", vehicle: "PNP-1067 (Mobile Patrol)" },
        { name: "Andres Villamor", license: "N02-17-607788", expiry: "Jan 20, 2028", status: "Valid", vehicle: "PNP-2210 (Patrol Motorcycle)" },
        { name: "Jose Aquino", license: "N02-14-809900", expiry: "Aug 14, 2027", status: "Valid", vehicle: "PNP-2234 (Patrol Motorcycle)" },
        { name: "Pedro Castillo", license: "N02-10-112233", expiry: "Apr 18, 2026", status: "Expired", vehicle: "PNP-1088 (Personnel Carrier)" }
      ],
      inspections: [
        { date: "Today", time: "06:40 AM", plate: "PNP-1021", driver: "Eduardo Lim", result: "All OK", remarks: "None", review: "Pending" },
        { date: "Today", time: "06:20 AM", plate: "PNP-2234", driver: "Jose Aquino", result: "Has Issue", remarks: "Chain slack beyond limit", review: "Pending" },
        { date: "Today", time: "06:05 AM", plate: "PNP-1067", driver: "Manuel Tan", result: "All OK", remarks: "None", review: "Reviewed" },
        { date: "Yesterday", time: "07:00 AM", plate: "PNP-1045", driver: "Roberto Salazar", result: "Has Issue", remarks: "Headlight alignment off", review: "Reviewed" }
      ],
      damage: [
        { date: "Today", time: "08:25 AM", plate: "PNP-1021", driver: "Eduardo Lim", nature: "Dented rear bumper after parking incident", parts: "Rear bumper", attachment: true, review: "Pending" },
        { date: "Yesterday", time: "03:30 PM", plate: "PNP-1088", driver: "Pedro Castillo", nature: "Engine overheating — coolant leak", parts: "Radiator", attachment: false, review: "Pending" },
        { date: "May 26, 2026", time: "10:10 AM", plate: "PNP-1045", driver: "Roberto Salazar", nature: "Cracked windshield from road debris", parts: "Windshield", attachment: false, review: "Reviewed" }
      ],
      pmActive: [
        { plate: "PNP-1088", vtype: "Personnel Carrier", target: "Engine Service", schedType: "Mileage-Based", dueMain: "102,000 km", dueSub: "Current: 102,560 km — overdue", status: "Due" },
        { plate: "PNP-1021", vtype: "Patrol Car", target: "Oil Change & Filter", schedType: "Mileage-Based", dueMain: "73,000 km", dueSub: "Current: 72,300 km", status: "Due Soon" },
        { plate: "PNP-1067", vtype: "Mobile Patrol", target: "Tire Rotation", schedType: "Time-Based", dueMain: "Nov 15, 2026", dueSub: "5 months away", status: "Upcoming" }
      ],
      pmCompleted: [
        { plate: "PNP-2210", vtype: "Patrol Motorcycle", performed: "Chain & Sprocket Service", date: "May 18, 2026", source: "Internal Office", parts: "Drive chain" }
      ],
      dispatch: [
        { mission: "Patrol", location: "Brgy. Trinidad, Calbayog", plate: "PNP-1045", driver: "Roberto Salazar", out: ["Today", "08:30 AM"], in: null, status: "Active" },
        { mission: "Patrol", location: "Brgy. Central, Calbayog", plate: "PNP-1021", driver: "Eduardo Lim", out: ["Jun 5, 2026", "06:00 AM"], in: ["Jun 5, 2026", "02:00 PM"], status: "Completed" },
        { mission: "Administrative Travel", location: "Provincial HQ, Catbalogan", plate: "PNP-1067", driver: "Manuel Tan", out: ["Jun 2, 2026", "08:00 AM"], in: ["Jun 2, 2026", "04:30 PM"], status: "Completed" },
        { mission: "Others", missionOther: "VIP Security Escort", location: "City Hall, Calbayog", plate: "PNP-2210", driver: "Andres Villamor", out: ["Jun 1, 2026", "09:00 AM"], in: ["Jun 1, 2026", "12:00 PM"], status: "Completed" }
      ],
      repairs: [
        { date: "Jun 6, 2026", plate: "PNP-1088", driver: "Pedro Castillo", scope: "Radiator assessment — coolant leak", parts: "None (assessment only)", cost: "—", source: "External Repair Shop", shop: "Northbay Auto Radiator Center", remarks: "Radiator replacement pending; remains Not Operational", vstatus: "Not Operational" },
        { date: "May 29, 2026", plate: "PNP-1045", driver: "Roberto Salazar", scope: "Windshield replacement", parts: "Front windshield", cost: "₱5,200", source: "External Repair Shop", shop: "ClearView Auto Glass Calbayog", remarks: "From damage report dated May 26; resolved", vstatus: "Operational" },
        { date: "May 21, 2026", plate: "PNP-2210", driver: "Andres Villamor", scope: "Brake adjustment and pad check", parts: "Brake pads (rear)", cost: "₱900", source: "Internal Office", remarks: "Routine motorcycle servicing", vstatus: "Operational" },
        { date: "May 14, 2026", plate: "PNP-1021", driver: "Eduardo Lim", scope: "Aircon recharge", parts: "Refrigerant", cost: "₱1,200", source: "GSO Motorpool", remarks: "Cabin cooling restored", vstatus: "Operational" }
      ],
      notifications: [
        { kind: "damage", detail: "PNP-1021 — Dented rear bumper.", time: "8:25 AM", group: "Today" },
        { kind: "inspection", detail: "PNP-2234 — 1 item flagged: chain slack.", time: "6:20 AM", group: "Today" },
        { kind: "inspection", detail: "PNP-1021 — All items OK.", time: "6:40 AM", group: "Today" },
        { kind: "license", detail: "Roberto Salazar — license expires Jun 30, 2026.", time: "9:00 AM", group: "Yesterday", tone: "warning", title: "License Expiring Soon" },
        { kind: "pm", detail: "PNP-1088 — engine service has reached its due mileage.", time: "8:00 AM", group: "Yesterday", title: "PM Due" },
        { kind: "license", detail: "Pedro Castillo — license expired on Apr 18, 2026.", time: "Apr 18", group: "Earlier", tone: "danger", title: "License Expired" }
      ]
    },

    CDRRMO: {
      name: "City Disaster Risk Reduction and Management Office", short: "CDRRMO", logo: "../assets/img/agency/cdrrmo-logo.jpg",
      location: "Calbayog City", contact: "(055) 301-2288", domain: "cdrrmo.calbayog.gov.ph", avatar: "A",
      extraItems: [], primaryMission: "Rescue Operation",
      frequentIssues: [
        { issue: "Hydraulic / winch system", count: 4, last: "Today", systemTag: "Hydraulics" },
        { issue: "Tires — sidewall / tread wear", count: 3, last: "May 24, 2026", systemTag: "Tires" },
        { issue: "Trailer / auxiliary lights", count: 3, last: "Yesterday", systemTag: "Lights" },
        { issue: "Battery — cold-start / charging", count: 2, last: "May 13, 2026", systemTag: "Battery" }
      ],
      vehicles: [
        { plate: "CDR-3301", type: "Rescue Truck", mm: "Isuzu ELF", driver: "Noel Ferrer", km: "47,800 km", status: "Operational", engine: "4HF1-RT-330218", chassis: "JAANKR55LH7700213" },
        { plate: "CDR-3312", type: "Rescue Boat Hauler", mm: "Ford Ranger", driver: "Victor Guevarra", km: "61,230 km", status: "Dispatched", engine: "P4AT-BH-551209", chassis: "MNCLMFF50JW440187" },
        { plate: "CDR-3325", type: "6-Wheeler Rescue", mm: "Isuzu Forward", driver: "Danilo Reyes", km: "75,640 km", status: "Operational", engine: "6HK1-FW-778120", chassis: "JALFTR34LK7001245" },
        { plate: "CDR-3340", type: "Rescue Ambulance", mm: "Toyota Hiace", driver: "Marvin Soriano", km: "52,110 km", status: "Under PM", engine: "1KD-RA-220914", chassis: "JTFSK22P3K0114520" },
        { plate: "CDR-3356", type: "Pickup", mm: "Mitsubishi Strada", driver: "Arnel Pascua", km: "69,900 km", status: "Operational", engine: "4N15-PK-660327", chassis: "MMBJYKL40KH052618" },
        { plate: "CDR-3370", type: "Service Truck", mm: "Hino 300", driver: "Benigno Lopez", km: "91,200 km", status: "Not Operational", engine: "N04C-ST-883011", chassis: "JHHADM2H50K112940" }
      ],
      drivers: [
        { name: "Noel Ferrer", license: "N03-15-220011", expiry: "May 9, 2028", status: "Valid", vehicle: "CDR-3301 (Rescue Truck)" },
        { name: "Victor Guevarra", license: "N03-13-330122", expiry: "Jul 2, 2026", status: "Expiring Soon", vehicle: "CDR-3312 (Rescue Boat Hauler)" },
        { name: "Danilo Reyes", license: "N03-16-440233", expiry: "Dec 1, 2027", status: "Valid", vehicle: "CDR-3325 (6-Wheeler Rescue)" },
        { name: "Marvin Soriano", license: "N03-17-550344", expiry: "Mar 25, 2028", status: "Valid", vehicle: "CDR-3340 (Rescue Ambulance)" },
        { name: "Arnel Pascua", license: "N03-14-660455", expiry: "Sep 16, 2027", status: "Valid", vehicle: "CDR-3356 (Pickup)" },
        { name: "Benigno Lopez", license: "N03-10-770566", expiry: "May 12, 2026", status: "Expired", vehicle: "CDR-3370 (Service Truck)" }
      ],
      inspections: [
        { date: "Today", time: "07:10 AM", plate: "CDR-3301", driver: "Noel Ferrer", result: "All OK", remarks: "None", review: "Pending" },
        { date: "Today", time: "06:55 AM", plate: "CDR-3325", driver: "Danilo Reyes", result: "Has Issue", remarks: "Winch cable frayed", review: "Pending" },
        { date: "Today", time: "06:30 AM", plate: "CDR-3356", driver: "Arnel Pascua", result: "All OK", remarks: "None", review: "Reviewed" },
        { date: "Yesterday", time: "07:20 AM", plate: "CDR-3312", driver: "Victor Guevarra", result: "Has Issue", remarks: "Trailer light not working", review: "Reviewed" }
      ],
      damage: [
        { date: "Today", time: "08:05 AM", plate: "CDR-3325", driver: "Danilo Reyes", nature: "Hydraulic winch failure during drill", parts: "Winch motor", attachment: true, review: "Pending" },
        { date: "Yesterday", time: "05:15 PM", plate: "CDR-3370", driver: "Benigno Lopez", nature: "Clutch failure — cannot shift gears", parts: "Clutch assembly", attachment: false, review: "Pending" },
        { date: "May 24, 2026", time: "11:40 AM", plate: "CDR-3301", driver: "Noel Ferrer", nature: "Flat tire — sidewall puncture", parts: "Rear tire", attachment: false, review: "Reviewed" }
      ],
      pmActive: [
        { plate: "CDR-3370", vtype: "Service Truck", target: "Engine Service", schedType: "Mileage-Based", dueMain: "91,000 km", dueSub: "Current: 91,200 km — overdue", status: "Due" },
        { plate: "CDR-3301", vtype: "Rescue Truck", target: "Oil Change & Filter", schedType: "Mileage-Based", dueMain: "48,000 km", dueSub: "Current: 47,800 km", status: "Due Soon" },
        { plate: "CDR-3325", vtype: "6-Wheeler Rescue", target: "Hydraulic System Check", schedType: "Time-Based", dueMain: "Oct 30, 2026", dueSub: "4 months away", status: "Upcoming" }
      ],
      pmCompleted: [
        { plate: "CDR-3340", vtype: "Rescue Ambulance", performed: "Coolant Flush & Inspection", date: "May 17, 2026", source: "GSO Motorpool", parts: "Coolant" }
      ],
      dispatch: [
        { mission: "Rescue Operation", location: "Brgy. Tinambacan, Calbayog", plate: "CDR-3312", driver: "Victor Guevarra", out: ["Today", "07:50 AM"], in: null, status: "Active" },
        { mission: "Rescue Operation", location: "Brgy. Oquendo, Calbayog", plate: "CDR-3301", driver: "Noel Ferrer", out: ["Jun 4, 2026", "01:20 PM"], in: ["Jun 4, 2026", "06:10 PM"], status: "Completed" },
        { mission: "Administrative Travel", location: "Regional DRRM Council, Tacloban", plate: "CDR-3356", driver: "Arnel Pascua", out: ["Jun 1, 2026", "07:30 AM"], in: ["Jun 1, 2026", "05:00 PM"], status: "Completed" },
        { mission: "Others", missionOther: "Tree-Planting Logistics Support", location: "Brgy. Cagsalay, Calbayog", plate: "CDR-3325", driver: "Danilo Reyes", out: ["May 31, 2026", "06:30 AM"], in: ["May 31, 2026", "01:00 PM"], status: "Completed" }
      ],
      repairs: [
        { date: "Jun 6, 2026", plate: "CDR-3370", driver: "Benigno Lopez", scope: "Clutch assembly assessment", parts: "None (assessment only)", cost: "—", source: "External Repair Shop", shop: "Samar Heavy Equipment Services", remarks: "Clutch overhaul pending; remains Not Operational", vstatus: "Not Operational" },
        { date: "May 28, 2026", plate: "CDR-3301", driver: "Noel Ferrer", scope: "Tire replacement (rear)", parts: "1x heavy-duty tire", cost: "₱8,400", source: "External Repair Shop", shop: "RoadGrip Tire Supply & Service", remarks: "From damage report dated May 24; resolved", vstatus: "Operational" },
        { date: "May 20, 2026", plate: "CDR-3325", driver: "Danilo Reyes", scope: "Hydraulic hose replacement", parts: "Hydraulic hose set", cost: "₱6,100", source: "Internal Office", remarks: "Restored winch operation", vstatus: "Operational" },
        { date: "May 13, 2026", plate: "CDR-3356", driver: "Arnel Pascua", scope: "Battery replacement", parts: "12V heavy-duty battery", cost: "₱5,500", source: "GSO Motorpool", remarks: "Cold-start issue resolved", vstatus: "Operational" }
      ],
      notifications: [
        { kind: "damage", detail: "CDR-3325 — Hydraulic winch failure.", time: "8:05 AM", group: "Today" },
        { kind: "inspection", detail: "CDR-3325 — 1 item flagged: winch cable frayed.", time: "6:55 AM", group: "Today" },
        { kind: "inspection", detail: "CDR-3301 — All items OK.", time: "7:10 AM", group: "Today" },
        { kind: "license", detail: "Victor Guevarra — license expires Jul 2, 2026.", time: "9:00 AM", group: "Yesterday", tone: "warning", title: "License Expiring Soon" },
        { kind: "pm", detail: "CDR-3370 — engine service has reached its due mileage.", time: "8:00 AM", group: "Yesterday", title: "PM Due" },
        { kind: "license", detail: "Benigno Lopez — license expired on May 12, 2026.", time: "May 12", group: "Earlier", tone: "danger", title: "License Expired" }
      ]
    },

    CHO: {
      name: "City Health Office", short: "CHO", logo: "../assets/img/agency/cho-logo.png",
      location: "Calbayog City", contact: "(055) 412-3390", domain: "cho.calbayog.gov.ph", avatar: "A",
      extraItems: [], primaryMission: "Medical Response",
      frequentIssues: [
        { issue: "Aircon / cabin cooling", count: 4, last: "Today", systemTag: "Cooling" },
        { issue: "Siren / signaling system", count: 3, last: "Yesterday", systemTag: "Signaling" },
        { issue: "Doors / stretcher latch", count: 3, last: "Today", systemTag: "Body" },
        { issue: "Brakes — fluid / pad wear", count: 2, last: "May 19, 2026", systemTag: "Brakes" }
      ],
      vehicles: [
        { plate: "CHO-4401", type: "Ambulance", mm: "Toyota Hiace", driver: "Grace Manalo", km: "58,400 km", status: "Operational", engine: "1KD-AM-441120", chassis: "JTFSK22P9L0119033" },
        { plate: "CHO-4412", type: "Ambulance", mm: "Nissan Urvan", driver: "Lourdes Bautista", km: "47,250 km", status: "Dispatched", engine: "YD25-AM-552031", chassis: "JN1TG4E26M0072145" },
        { plate: "CHO-4423", type: "Medical Van", mm: "Hyundai Starex", driver: "Teresa Domingo", km: "63,770 km", status: "Operational", engine: "D4CB-MV-663142", chassis: "KMJWA37KBLU205418" },
        { plate: "CHO-4434", type: "Mobile Clinic", mm: "Isuzu ELF", driver: "Ramil Ocampo", km: "80,120 km", status: "Under PM", engine: "4HF1-MC-774253", chassis: "JAANKR55LL7702890" },
        { plate: "CHO-4445", type: "Patient Transport", mm: "Toyota Hiace", driver: "Cynthia Flores", km: "39,980 km", status: "Operational", engine: "2KD-PT-885364", chassis: "JTFSS22P7M0127651" },
        { plate: "CHO-4456", type: "Service Vehicle", mm: "Mitsubishi L300", driver: "Alberto Reyes", km: "94,300 km", status: "Not Operational", engine: "4D56-SV-996475", chassis: "MMBJNKA70LD041208" }
      ],
      drivers: [
        { name: "Grace Manalo", license: "N04-15-330044", expiry: "Feb 28, 2028", status: "Valid", vehicle: "CHO-4401 (Ambulance)" },
        { name: "Lourdes Bautista", license: "N04-13-440155", expiry: "Jul 5, 2026", status: "Expiring Soon", vehicle: "CHO-4412 (Ambulance)" },
        { name: "Teresa Domingo", license: "N04-16-550266", expiry: "Nov 19, 2027", status: "Valid", vehicle: "CHO-4423 (Medical Van)" },
        { name: "Ramil Ocampo", license: "N04-17-660377", expiry: "Apr 7, 2028", status: "Valid", vehicle: "CHO-4434 (Mobile Clinic)" },
        { name: "Cynthia Flores", license: "N04-14-770488", expiry: "Oct 22, 2027", status: "Valid", vehicle: "CHO-4445 (Patient Transport)" },
        { name: "Alberto Reyes", license: "N04-10-880599", expiry: "May 20, 2026", status: "Expired", vehicle: "CHO-4456 (Service Vehicle)" }
      ],
      inspections: [
        { date: "Today", time: "07:25 AM", plate: "CHO-4401", driver: "Grace Manalo", result: "All OK", remarks: "None", review: "Pending" },
        { date: "Today", time: "07:00 AM", plate: "CHO-4434", driver: "Ramil Ocampo", result: "Has Issue", remarks: "Aircon not cooling", review: "Pending" },
        { date: "Today", time: "06:45 AM", plate: "CHO-4423", driver: "Teresa Domingo", result: "All OK", remarks: "None", review: "Reviewed" },
        { date: "Yesterday", time: "07:30 AM", plate: "CHO-4412", driver: "Lourdes Bautista", result: "Has Issue", remarks: "Siren intermittent", review: "Reviewed" }
      ],
      damage: [
        { date: "Today", time: "08:15 AM", plate: "CHO-4401", driver: "Grace Manalo", nature: "Side door latch broken — won't lock", parts: "Door latch assembly", attachment: true, review: "Pending" },
        { date: "Yesterday", time: "04:10 PM", plate: "CHO-4456", driver: "Alberto Reyes", nature: "Engine won't start — suspected alternator", parts: "Alternator", attachment: false, review: "Pending" },
        { date: "May 27, 2026", time: "10:30 AM", plate: "CHO-4412", driver: "Lourdes Bautista", nature: "Stretcher rail bent on loading", parts: "Stretcher rail", attachment: false, review: "Reviewed" }
      ],
      pmActive: [
        { plate: "CHO-4456", vtype: "Service Vehicle", target: "Engine Service", schedType: "Mileage-Based", dueMain: "94,000 km", dueSub: "Current: 94,300 km — overdue", status: "Due" },
        { plate: "CHO-4401", vtype: "Ambulance", target: "Oil Change & Filter", schedType: "Mileage-Based", dueMain: "59,000 km", dueSub: "Current: 58,400 km", status: "Due Soon" },
        { plate: "CHO-4423", vtype: "Medical Van", target: "Aircon Servicing", schedType: "Time-Based", dueMain: "Dec 5, 2026", dueSub: "6 months away", status: "Upcoming" }
      ],
      pmCompleted: [
        { plate: "CHO-4445", vtype: "Patient Transport", performed: "Brake Inspection & Fluid", date: "May 19, 2026", source: "GSO Motorpool", parts: "Brake fluid" }
      ],
      dispatch: [
        { mission: "Medical Response", location: "Brgy. Bagacay, Calbayog", plate: "CHO-4412", driver: "Lourdes Bautista", out: ["Today", "09:10 AM"], in: null, status: "Active" },
        { mission: "Medical Response", location: "Brgy. Hamorawon, Calbayog", plate: "CHO-4401", driver: "Grace Manalo", out: ["Jun 5, 2026", "10:30 AM"], in: ["Jun 5, 2026", "01:15 PM"], status: "Completed" },
        { mission: "Medical Response", location: "Brgy. Dagum, Calbayog", plate: "CHO-4423", driver: "Teresa Domingo", out: ["Jun 3, 2026", "02:00 PM"], in: ["Jun 3, 2026", "04:40 PM"], status: "Completed" },
        { mission: "Others", missionOther: "Community Health Caravan Support", location: "Brgy. Tinambacan, Calbayog", plate: "CHO-4445", driver: "Cynthia Flores", out: ["Jun 2, 2026", "07:00 AM"], in: ["Jun 2, 2026", "03:30 PM"], status: "Completed" }
      ],
      repairs: [
        { date: "Jun 6, 2026", plate: "CHO-4456", driver: "Alberto Reyes", scope: "Alternator assessment — no-start", parts: "None (assessment only)", cost: "—", source: "External Repair Shop", shop: "PowerLine Auto Electrical Shop", remarks: "Alternator replacement pending; remains Not Operational", vstatus: "Not Operational" },
        { date: "May 30, 2026", plate: "CHO-4412", driver: "Lourdes Bautista", scope: "Stretcher rail straightening", parts: "Stretcher rail", cost: "₱2,300", source: "Internal Office", remarks: "From damage report dated May 27; resolved", vstatus: "Operational" },
        { date: "May 23, 2026", plate: "CHO-4401", driver: "Grace Manalo", scope: "Siren control unit replacement", parts: "Siren controller", cost: "₱3,700", source: "External Repair Shop", shop: "Siren & Signal Specialists Inc.", remarks: "Emergency signaling restored", vstatus: "Operational" },
        { date: "May 15, 2026", plate: "CHO-4423", driver: "Teresa Domingo", scope: "Aircon compressor service", parts: "Compressor seal kit", cost: "₱4,100", source: "GSO Motorpool", remarks: "Patient cabin cooling restored", vstatus: "Operational" }
      ],
      notifications: [
        { kind: "damage", detail: "CHO-4401 — Side door latch broken.", time: "8:15 AM", group: "Today" },
        { kind: "inspection", detail: "CHO-4434 — 1 item flagged: aircon not cooling.", time: "7:00 AM", group: "Today" },
        { kind: "inspection", detail: "CHO-4423 — All items OK.", time: "6:45 AM", group: "Today" },
        { kind: "license", detail: "Lourdes Bautista — license expires Jul 5, 2026.", time: "9:00 AM", group: "Yesterday", tone: "warning", title: "License Expiring Soon" },
        { kind: "pm", detail: "CHO-4456 — engine service has reached its due mileage.", time: "8:00 AM", group: "Yesterday", title: "PM Due" },
        { kind: "license", detail: "Alberto Reyes — license expired on May 20, 2026.", time: "May 20", group: "Earlier", tone: "danger", title: "License Expired" }
      ]
    }
  };

  /* --------------------------- resolve agency -------------------------- */
  function currentKey() {
    const q = new URLSearchParams(location.search).get("agency");
    if (q && AGENCIES[q]) { localStorage.setItem("rvms_agency", q); return q; }
    const saved = localStorage.getItem("rvms_agency");
    return (saved && AGENCIES[saved]) ? saved : "BFP";
  }
  const KEY = currentKey();
  const A = AGENCIES[KEY];
  // Keep agency in the querystring as the user navigates internal links.
  function decorateLinks() {
    document.querySelectorAll('a[href$=".html"]').forEach(a => {
      const href = a.getAttribute("href");
      // Match on the file name so paths like ../login.html are still skipped.
      const base = href.split("/").pop();
      if (/^https?:/i.test(href) || base.startsWith("login") || base.startsWith("signup") || base.startsWith("index")) return;
      if (href.includes("agency=")) return;
      a.setAttribute("href", href + (href.includes("?") ? "&" : "?") + "agency=" + KEY);
    });
  }

  /* ----------------------------- chrome -------------------------------- */
  function renderChrome() {
    document.querySelectorAll(".js-agency-badge").forEach(el => {
      el.innerHTML = '<img src="' + A.logo + '" alt="" class="agency-badge-logo me-2">' + esc(A.name);
      el.classList.add("d-inline-flex", "align-items-center");
    });
    const email = "admin@" + A.domain;
    document.querySelectorAll(".js-agency-name").forEach(el => el.textContent = A.name);
    document.querySelectorAll(".js-agency-location").forEach(el => el.value !== undefined ? el.value = A.location : el.textContent = A.location);
    document.querySelectorAll(".js-agency-contact").forEach(el => el.value !== undefined ? el.value = A.contact : el.textContent = A.contact);
    document.querySelectorAll(".js-agency-email").forEach(el => el.value !== undefined ? el.value = email : el.textContent = email);
    document.querySelectorAll(".js-agency-name-input").forEach(el => el.value = A.name);
    const avatar = document.querySelector(".js-agency-avatar");
    if (avatar) avatar.innerHTML = '<img src="' + A.logo + '" alt="" style="width:100%;height:100%;border-radius:50%;object-fit:cover;">';
  }

  /* ----------------------------- bell ---------------------------------- */
  function bellItemHTML(n) {
    const meta = NOTIF[n.kind];
    const tone = n.tone || meta.tone;
    const title = n.title || meta.title;
    return '<li><a class="dropdown-item notif-item d-flex align-items-start gap-2 py-2 border-bottom" href="' + meta.link + '">' +
      '<span class="notif-icon rounded-circle bg-' + tone + ' bg-opacity-10 text-' + tone + ' d-inline-flex justify-content-center align-items-center"><i class="bi ' + meta.icon + '"></i></span>' +
      '<span><span class="small fw-bold d-block">' + esc(title) + '</span>' +
      '<span class="small text-secondary d-block">' + esc(n.detail) + '</span>' +
      '<span class="text-secondary d-block" style="font-size:0.7rem;">' + n.group + ', ' + n.time + '</span></span></a></li>';
  }
  function renderBell() {
    const ul = document.querySelector(".js-bell-list");
    if (!ul) return;
    const items = A.notifications.filter(n => n.group === "Today" || n.group === "Yesterday");
    ul.innerHTML =
      '<li class="d-flex justify-content-between align-items-center px-3 py-2 border-bottom">' +
      '<h6 class="mb-0 fw-bold small">Notifications</h6>' +
      '<span class="badge bg-danger rounded-pill">' + items.length + ' new</span></li>' +
      items.map(bellItemHTML).join("") +
      '<li><a class="dropdown-item text-center small text-primary fw-semibold py-2" href="notifications.html">View All Notifications</a></li>';
    const count = document.querySelector(".js-bell-count");
    if (count) count.textContent = items.length;
  }

  /* --------------------------- page renderers -------------------------- */
  function setRows(id, html) { const el = document.getElementById(id); if (el) el.innerHTML = html; }

  function renderVehicles() {
    const el = document.getElementById("rows-vehicles");
    if (!el) return;
    el.innerHTML = A.vehicles.map(v =>
      '<tr data-plate="' + v.plate + '" data-type="' + v.type + '" data-makemodel="' + v.mm + '" data-driver="' + v.driver + '" data-mileage="' + v.km + '" data-status="' + v.status + '" data-badge="' + STATUS_BADGE[v.status] + '" data-engine="' + v.engine + '" data-chassis="' + v.chassis + '">' +
      '<td class="fw-bold">' + v.plate + '</td>' +
      '<td><div class="fw-semibold">' + v.type + '</div><div class="small text-secondary">' + v.mm + '</div></td>' +
      '<td>' + v.driver + '</td><td>' + v.km + '</td>' +
      '<td><span class="badge status-badge ' + STATUS_BADGE[v.status] + ' px-3 py-2 rounded-pill">' + statusLabel(v.status) + '</span></td>' +
      '<td class="text-end">' +
      '<button class="btn btn-sm btn-light border" title="View Details" data-bs-toggle="modal" data-bs-target="#viewVehicleModal"><i class="bi bi-eye"></i></button> ' +
      '<button class="btn btn-sm btn-light border" title="Edit" data-bs-toggle="modal" data-bs-target="#editVehicleModal"><i class="bi bi-pencil"></i></button> ' +
      '<button class="btn btn-sm btn-light border" title="Update Status" data-bs-toggle="modal" data-bs-target="#updateStatusModal"><i class="bi bi-arrow-repeat"></i></button>' +
      '</td></tr>'
    ).join("");
    const foot = document.querySelector(".js-vehicle-count");
    if (foot) foot.textContent = "Showing 1 to " + A.vehicles.length + " of " + A.vehicles.length + " vehicles";
  }

  function renderDrivers() {
    const el = document.getElementById("rows-drivers");
    if (!el) return;
    el.innerHTML = A.drivers.map(d => {
      const tone = LIC[d.status];
      return '<tr data-name="' + esc(d.name) + '" data-email="' + driverEmail(d) + '" data-license="' + d.license + '" data-expiry="' + d.expiry + '" data-status="' + d.status + '" data-vehicle="' + d.vehicle + '">' +
        '<td><div class="fw-bold">' + esc(d.name) + '</div><div class="small text-secondary">' + driverEmail(d) + '</div></td>' +
        '<td class="font-monospace text-secondary">' + d.license + '</td>' +
        '<td class="' + expiryClass(d.status) + '">' + d.expiry + '</td>' +
        '<td><span class="badge bg-' + tone + ' bg-opacity-10 text-' + tone + ' px-3 py-2 rounded-pill">' + d.status + '</span></td>' +
        '<td>' + d.vehicle + '</td>' +
        '<td class="text-end">' +
        '<button class="btn btn-sm btn-light border" title="View Details" data-bs-toggle="modal" data-bs-target="#viewDriverModal"><i class="bi bi-eye"></i></button> ' +
        '<button class="btn btn-sm btn-light border" title="Edit" data-bs-toggle="modal" data-bs-target="#editDriverModal"><i class="bi bi-pencil"></i></button> ' +
        '<button class="btn btn-sm btn-light border" title="Update License" data-bs-toggle="modal" data-bs-target="#updateLicenseModal"><i class="bi bi-arrow-clockwise"></i></button>' +
        '</td></tr>';
    }).join("");
    const valid = A.drivers.filter(d => d.status === "Valid").length;
    const soon = A.drivers.filter(d => d.status === "Expiring Soon").length;
    const exp = A.drivers.filter(d => d.status === "Expired").length;
    setText(".js-lic-valid", valid); setText(".js-lic-soon", soon); setText(".js-lic-expired", exp);
  }
  function driverEmail(d) {
    return d.name.toLowerCase().replace(/[^a-z ]/g, "").split(" ").slice(0, 2).join(".") + "@" + A.domain;
  }

  function renderInspections() {
    const el = document.getElementById("rows-inspections");
    if (!el) return;
    el.innerHTML = A.inspections.map(i => {
      const resBadge = i.result === "All OK" ? "badge-operational" : "badge-not-operational";
      const actions = i.review === "Pending"
        ? '<button class="btn btn-sm btn-light border w-100" data-bs-toggle="modal" data-bs-target="#viewChecklistModal">View Checklist</button>' +
          '<button class="btn btn-sm btn-danger fw-medium w-100" data-bs-toggle="modal" data-bs-target="#reviewInspectionModal">Review &amp; Assess</button>'
        : '<button class="btn btn-sm btn-light border w-100" data-bs-toggle="modal" data-bs-target="#viewChecklistModal">View Checklist</button>';
      return '<tr data-plate="' + i.plate + '" data-type="' + vtypeOf(i.plate) + '" data-driver="' + esc(i.driver) + '" data-when="' + i.date + ', ' + i.time + '" data-result="' + i.result + '" data-remarks="' + esc(i.remarks) + '">' +
        '<td><div class="fw-bold text-dark">' + i.date + '</div><div class="small text-secondary">' + i.time + '</div></td>' +
        '<td><div class="fw-bold text-dark">' + i.plate + '</div><div class="small text-secondary">' + i.driver + '</div></td>' +
        '<td><span class="badge ' + resBadge + ' px-3 py-2 rounded-pill">' + i.result + '</span></td>' +
        '<td class="text-secondary">' + (i.remarks === "None" ? '<em class="small">None</em>' : esc(i.remarks)) + '</td>' +
        '<td><span class="badge ' + REVIEW_BADGE[i.review] + ' px-3 py-2 rounded-pill">' + i.review + '</span></td>' +
        '<td class="text-end"><div class="d-inline-flex flex-column gap-2" style="min-width:150px;">' + actions + '</div></td></tr>';
    }).join("");
    setText(".js-insp-pending", A.inspections.filter(i => i.review === "Pending").length + " Pending Review");
  }

  function renderDamage() {
    const el = document.getElementById("rows-damage");
    if (!el) return;
    el.innerHTML = A.damage.map(d => {
      const attach = d.attachment
        ? '<button class="btn btn-sm btn-light border"><i class="bi bi-image text-primary me-1"></i> View</button>'
        : '<em class="text-secondary small">None</em>';
      const actions = d.review === "Pending"
        ? '<div class="d-inline-flex flex-column gap-2" style="min-width:150px;"><button class="btn btn-sm btn-danger fw-medium w-100" data-bs-toggle="modal" data-bs-target="#reviewDamageModal">Review &amp; Assess</button></div>'
        : '<em class="text-secondary small">Resolved — see Repair Logs</em>';
      return '<tr data-plate="' + d.plate + '" data-type="' + vtypeOf(d.plate) + '" data-driver="' + esc(d.driver) + '" data-when="' + d.date + ', ' + d.time + '" data-nature="' + esc(d.nature) + '" data-parts="' + esc(d.parts) + '" data-attachment="' + d.attachment + '">' +
        '<td><div class="fw-bold text-dark">' + d.date + '</div><div class="small text-secondary">' + d.time + '</div></td>' +
        '<td><div class="fw-bold text-dark">' + d.plate + '</div><div class="small text-secondary">' + d.driver + '</div></td>' +
        '<td><div class="fw-medium text-dark text-truncate" style="max-width:250px;">' + esc(d.nature) + '</div></td>' +
        '<td class="text-secondary">' + esc(d.parts) + '</td>' +
        '<td>' + attach + '</td>' +
        '<td><span class="badge ' + REVIEW_BADGE[d.review] + ' px-3 py-2 rounded-pill">' + d.review + '</span></td>' +
        '<td class="text-end">' + actions + '</td></tr>';
    }).join("");
    setText(".js-damage-pending", A.damage.filter(d => d.review === "Pending").length + " Pending Review");
  }

  function renderChecklistExtra() {
    const sec = document.getElementById("checklist-extra");
    if (!sec) return;
    if (!A.extraItems.length) { sec.innerHTML = ""; return; }
    sec.innerHTML = '<h6 class="fw-bold border-bottom pb-2 mb-3 text-warning">' + A.short + ' Additional Items (' + A.extraItems.length + ')</h6>' +
      '<div class="row g-3">' + A.extraItems.map(it =>
        '<div class="col-md-6"><i class="bi bi-check-circle-fill text-success me-2"></i> ' + esc(it) + '</div>').join("") + '</div>';
  }

  function renderPM() {
    const act = document.getElementById("rows-pm-active");
    if (act) {
      act.innerHTML = A.pmActive.map(p => {
        const dueColor = p.status === "Due" ? "text-danger" : p.status === "Due Soon" ? "text-warning text-dark" : "text-dark";
        const action = p.status === "Upcoming"
          ? '<button class="btn btn-sm btn-light border" data-bs-toggle="modal" data-bs-target="#editPmModal"><i class="bi bi-pencil"></i></button>'
          : '<button class="btn btn-sm btn-success fw-medium" data-bs-toggle="modal" data-bs-target="#markCompletedModal">Mark Completed</button>';
        return '<tr><td><div class="fw-bold">' + p.plate + '</div><div class="small text-secondary">' + p.vtype + '</div></td>' +
          '<td><div class="fw-semibold">' + esc(p.target) + '</div></td>' +
          '<td><div class="badge bg-light text-dark border">' + p.schedType + '</div></td>' +
          '<td><div class="' + dueColor + ' fw-bold">' + p.dueMain + '</div><div class="small text-secondary">' + esc(p.dueSub) + '</div></td>' +
          '<td><span class="badge ' + PM_BADGE[p.status] + ' px-3 py-2 rounded-pill">' + p.status + '</span></td>' +
          '<td class="text-end">' + action + '</td></tr>';
      }).join("");
      setText(".js-pm-active-count", A.pmActive.length);
    }
    const comp = document.getElementById("rows-pm-completed");
    if (comp) {
      comp.innerHTML = A.pmCompleted.map(p =>
        '<tr><td><div class="fw-bold text-dark">' + p.plate + '</div><div class="small text-secondary">' + p.vtype + '</div></td>' +
        '<td><div class="fw-semibold text-dark">' + esc(p.performed) + '</div></td>' +
        '<td><div class="fw-medium text-dark">' + p.date + '</div></td>' +
        '<td><span class="badge bg-light text-dark border">' + p.source + '</span></td>' +
        '<td><div class="fw-medium text-dark">' + esc(p.parts) + '</div></td>' +
        '<td><span class="badge bg-secondary px-3 py-2 rounded-pill">Completed</span></td></tr>'
      ).join("");
    }
  }

  function renderDispatch() {
    const el = document.getElementById("rows-dispatch");
    if (!el) return;
    el.innerHTML = A.dispatch.map(d => {
      const timeIn = d.in ? '<div class="fw-medium text-dark">' + d.in[0] + '</div><div class="small text-secondary">' + d.in[1] + '</div>' : '<em class="text-secondary small">--</em>';
      const statusBadge = d.status === "Active"
        ? '<span class="badge bg-primary px-3 py-2 rounded-pill">Active</span>'
        : '<span class="badge bg-secondary px-3 py-2 rounded-pill">Completed</span>';
      const editBtn = '<button class="btn btn-sm btn-light border" title="Edit" data-bs-toggle="modal" data-bs-target="#editDispatchModal"><i class="bi bi-pencil"></i></button>';
      const mainBtn = d.status === "Active"
        ? '<button class="btn btn-sm btn-primary fw-medium text-nowrap" data-bs-toggle="modal" data-bs-target="#closeDispatchModal">Close Dispatch</button>'
        : '<button class="btn btn-sm btn-light border" title="View" data-bs-toggle="modal" data-bs-target="#viewDispatchModal"><i class="bi bi-eye"></i></button>';
      return '<tr data-mission="' + esc(d.mission) + '" data-mission-other="' + esc(d.missionOther || "") + '" data-location="' + esc(d.location) + '" data-plate="' + d.plate + '" data-type="' + vtypeOf(d.plate) + '" data-driver="' + esc(d.driver) + '" data-out="' + d.out[0] + ', ' + d.out[1] + '" data-in="' + (d.in ? d.in[0] + ', ' + d.in[1] : '') + '" data-status="' + d.status + '">' +
        '<td><div class="fw-bold text-dark">' + esc(missionLabel(d)) + '</div><div class="small text-secondary"><i class="bi bi-geo-alt me-1"></i>' + esc(d.location) + '</div></td>' +
        '<td><div class="fw-semibold text-dark">' + d.plate + '</div><div class="small text-secondary">' + d.driver + '</div></td>' +
        '<td><div class="fw-medium text-dark">' + d.out[0] + '</div><div class="small text-secondary">' + d.out[1] + '</div></td>' +
        '<td>' + timeIn + '</td><td>' + statusBadge + '</td>' +
        '<td class="text-end"><div class="d-inline-flex flex-nowrap gap-2 justify-content-end">' + editBtn + mainBtn + '</div></td></tr>';
    }).join("");
    const active = A.dispatch.filter(d => d.status === "Active").length;
    const alert = document.querySelector(".js-dispatch-alert");
    if (alert) alert.innerHTML = '<strong>Active Monitoring:</strong> There ' + (active === 1 ? "is" : "are") + ' currently <strong>' + active + '</strong> vehicle' + (active === 1 ? "" : "s") + ' deployed in the field.';
  }

  function renderRepairs() {
    const el = document.getElementById("rows-repairs");
    if (!el) return;
    el.innerHTML = A.repairs.map(r =>
      '<tr data-date="' + r.date + '" data-plate="' + r.plate + '" data-type="' + vtypeOf(r.plate) + '" data-driver="' + esc(r.driver) + '" data-scope="' + esc(r.scope) + '" data-parts="' + esc(r.parts) + '" data-cost="' + r.cost + '" data-source="' + r.source + '" data-shop="' + esc(r.shop || "") + '" data-remarks="' + esc(r.remarks) + '" data-vstatus="' + r.vstatus + '" data-badge="' + STATUS_BADGE[r.vstatus] + '">' +
      '<td class="fw-medium">' + r.date + '</td>' +
      '<td><div class="fw-bold">' + r.plate + '</div><div class="small text-secondary">' + r.driver + '</div></td>' +
      '<td>' + esc(r.scope) + '</td>' +
      '<td>' + (r.parts.startsWith("None") ? '<em class="text-secondary small">' + esc(r.parts) + '</em>' : esc(r.parts)) + '</td>' +
      '<td>' + (r.cost === "—" ? '<em class="text-secondary small">—</em>' : r.cost) + '</td>' +
      '<td><span class="badge bg-light text-dark border">' + repairSourceLabel(r) + '</span></td>' +
      '<td class="small text-secondary">' + esc(r.remarks) + '</td>' +
      '<td><span class="badge status-badge ' + STATUS_BADGE[r.vstatus] + ' px-3 py-2 rounded-pill">' + statusLabel(r.vstatus) + '</span></td>' +
      '<td class="text-end"><div class="d-inline-flex gap-2 justify-content-end">' +
      '<button class="btn btn-sm btn-light border" title="Edit Log" data-bs-toggle="modal" data-bs-target="#editRepairModal"><i class="bi bi-pencil"></i></button>' +
      '<button class="btn btn-sm btn-light border" title="Update Vehicle Status" data-bs-toggle="modal" data-bs-target="#updateStatusModal"><i class="bi bi-arrow-repeat"></i></button>' +
      '</div></td></tr>'
    ).join("");
    setText(".js-repair-count", "Showing 1 to " + A.repairs.length + " of " + A.repairs.length + " repair logs");
  }

  function renderDashboard() {
    if (!document.querySelector(".js-metric-total")) return;
    const v = A.vehicles;
    const count = s => v.filter(x => x.status === s).length;
    setText(".js-metric-total", v.length);
    setText(".js-metric-operational", count("Operational"));
    setText(".js-metric-dispatched", count("Dispatched"));
    setText(".js-metric-underpm", count("Under PM"));
    setText(".js-metric-notop", count("Not Operational"));
    setText(".js-metric-drivers", A.drivers.length);
    const expiring = A.drivers.filter(d => d.status !== "Valid").length;
    setText(".js-metric-expiring", expiring);
    const pendingDamage = A.damage.filter(d => d.review === "Pending").length;
    setText(".js-metric-damage", pendingDamage);

    // Action Required — pending inspections & damage
    const pending = [];
    A.damage.filter(d => d.review === "Pending").forEach(d => pending.push({ plate: d.plate, type: vtypeOf(d.plate), label: "Damage: " + d.nature, when: d.date + ", " + d.time }));
    A.inspections.filter(i => i.review === "Pending" && i.result === "Has Issue").forEach(i => pending.push({ plate: i.plate, type: vtypeOf(i.plate), label: "BLOWBAGETS issue: " + i.remarks, when: i.date + ", " + i.time }));
    const pendList = document.querySelector(".js-action-pending");
    if (pendList) {
      pendList.innerHTML = pending.slice(0, 3).map(p =>
        '<a href="inspections-damage.html" class="list-group-item list-group-item-action py-3">' +
        '<div class="d-flex w-100 justify-content-between"><h6 class="mb-1 fw-bold">' + p.type + ' (' + p.plate + ')</h6><small class="text-secondary">' + p.when + '</small></div>' +
        '<p class="mb-1 small">' + esc(p.label) + '</p><small class="text-danger fw-semibold">Action: Needs Review</small></a>'
      ).join("");
      setText(".js-action-pending-count", pending.length + " New");
    }
    // Action Required — expiring licenses
    const lic = A.drivers.filter(d => d.status !== "Valid");
    const licList = document.querySelector(".js-action-licenses");
    if (licList) {
      licList.innerHTML = lic.map(d => {
        const badge = d.status === "Expired" ? '<span class="badge bg-danger">Expired</span>' : '<span class="badge bg-warning text-dark">Expiring Soon</span>';
        const sub = d.status === "Expired" ? "Expired: " + d.expiry + " — renewal required" : "Expiry: " + d.expiry;
        return '<a href="drivers.html" class="list-group-item list-group-item-action py-3">' +
          '<div class="d-flex w-100 justify-content-between"><h6 class="mb-1 fw-bold">' + esc(d.name) + '</h6>' + badge + '</div>' +
          '<p class="mb-1 small">License: ' + d.license + '</p><small class="text-secondary fw-semibold">' + sub + '</small></a>';
      }).join("");
      setText(".js-action-licenses-count", lic.length + " Warning" + (lic.length === 1 ? "" : "s"));
    }
  }
  function vtypeOf(plate) { const f = A.vehicles.find(x => x.plate === plate); return f ? f.type : ""; }

  function renderNotificationsPage() {
    const wrap = document.getElementById("notif-groups");
    if (!wrap) return;
    const groups = ["Today", "Yesterday", "Earlier"];
    wrap.innerHTML = groups.map(g => {
      const items = A.notifications.filter(n => n.group === g);
      if (!items.length) return "";
      return '<h6 class="fw-bold text-secondary text-uppercase small mb-3">' + g + '</h6>' +
        '<div class="card border-0 shadow-sm rounded-3 overflow-hidden mb-4"><div class="list-group list-group-flush">' +
        items.map(n => {
          const meta = NOTIF[n.kind];
          const tone = n.tone || meta.tone;
          const title = n.title || meta.title;
          return '<a href="' + meta.link + '" class="list-group-item list-group-item-action d-flex align-items-start gap-3 py-3">' +
            '<span class="notif-icon rounded-circle bg-' + tone + ' bg-opacity-10 text-' + tone + ' d-inline-flex justify-content-center align-items-center mt-1"><i class="bi ' + meta.icon + '"></i></span>' +
            '<span class="flex-grow-1"><span class="fw-bold d-block">' + esc(title) + '</span><span class="small text-secondary d-block">' + esc(n.detail) + '</span></span>' +
            '<span class="small text-secondary text-nowrap">' + n.time + '</span></a>';
        }).join("") + '</div></div>';
    }).join("");
  }

  /* ------------------ option lists (modals/filters) -------------------- */
  function renderOptionLists() {
    const vehOpts = A.vehicles.map(v => v.plate + " (" + v.type + ")");
    const drvOpts = A.drivers.map(d => d.name);
    document.querySelectorAll(".js-vehicle-options").forEach(sel => {
      const keepFirst = sel.dataset.keepFirst === "true";
      const lead = keepFirst && sel.options.length ? '<option value="">' + sel.options[0].text + "</option>" : "";
      sel.innerHTML = lead + vehOpts.map(o => "<option>" + o + "</option>").join("");
    });
    document.querySelectorAll(".js-driver-options").forEach(sel => {
      const keepFirst = sel.dataset.keepFirst === "true";
      const lead = keepFirst && sel.options.length ? '<option value="">' + sel.options[0].text + "</option>" : "";
      sel.innerHTML = lead + drvOpts.map(o => "<option>" + o + "</option>").join("");
    });
  }

  /* ----------------- data-driven driver modals ------------------------- */
  function wireDriverModals() {
    const rowData = ev => { const r = ev.relatedTarget && ev.relatedTarget.closest("tr"); return r ? r.dataset : null; };
    const view = document.getElementById("viewDriverModal");
    if (view) view.addEventListener("show.bs.modal", ev => {
      const d = rowData(ev); if (!d) return;
      setText("#vdName", d.name); setText("#vdEmail", d.email); setText("#vdLicense", d.license);
      setText("#vdExpiry", d.expiry); setText("#vdVehicle", d.vehicle);
      const b = document.getElementById("vdStatusBadge");
      if (b) { b.className = "badge bg-" + LIC[d.status] + " bg-opacity-10 text-" + LIC[d.status] + " px-3 py-1 rounded-pill"; b.textContent = "License " + d.status; }
    });
    const edit = document.getElementById("editDriverModal");
    if (edit) edit.addEventListener("show.bs.modal", ev => {
      const d = rowData(ev); if (!d) return;
      setVal("#edName", d.name); setVal("#edEmail", d.email); setVal("#edLicense", d.license);
    });
    const lic = document.getElementById("updateLicenseModal");
    if (lic) {
      const newDate = document.getElementById("ulNewExpiry");
      const result = document.getElementById("ulResult");
      const computeStatus = str => {
        if (!str) return null;
        const days = (new Date(str) - new Date()) / 86400000;
        return days < 0 ? "Expired" : days <= 60 ? "Expiring Soon" : "Valid";
      };
      const showResult = () => {
        const st = computeStatus(newDate.value);
        if (!st) { result.textContent = "—"; result.className = "fw-bold"; return; }
        result.textContent = st;
        result.className = "fw-bold text-" + LIC[st];
      };
      lic.addEventListener("show.bs.modal", ev => {
        const d = rowData(ev); if (!d) return;
        setText("#ulName", d.name); setText("#ulLicense", d.license); setVal("#ulCurrentExpiry", d.expiry);
        const b = document.getElementById("ulCurrentBadge");
        if (b) { b.className = "badge bg-" + LIC[d.status] + " bg-opacity-10 text-" + LIC[d.status] + " px-3 py-2 rounded-pill"; b.textContent = d.status; }
        newDate.value = ""; showResult();
      });
      newDate.addEventListener("input", showResult);
      const addYears = y => {
        const dt = new Date();
        dt.setFullYear(dt.getFullYear() + y);
        newDate.value = dt.toISOString().slice(0, 10);
        showResult();
      };
      const p5 = document.getElementById("ulPlus5"); if (p5) p5.addEventListener("click", () => addYears(5));
      const p10 = document.getElementById("ulPlus10"); if (p10) p10.addEventListener("click", () => addYears(10));
    }
  }

  /* --------------- data-driven review / edit modals -------------------- */
  function modalRow(ev) { const r = ev.relatedTarget && ev.relatedTarget.closest("tr"); return r ? r.dataset : null; }

  function wireReviewModals() {
    const ri = document.getElementById("reviewInspectionModal");
    if (ri) ri.addEventListener("show.bs.modal", ev => {
      const d = modalRow(ev); if (!d) return;
      setText("#riVehicle", d.plate + " (" + d.type + ")");
      setText("#riDriver", d.driver); setText("#riWhen", d.when);
      const b = document.getElementById("riResultBadge");
      if (b) { const ok = d.result === "All OK"; b.className = "badge " + (ok ? "badge-operational" : "badge-not-operational") + " px-3 py-2 rounded-pill"; b.textContent = d.result; }
      setText("#riRemarks", (d.remarks && d.remarks !== "None") ? d.remarks : "No issues reported — all BLOWBAGETS items OK.");
    });
    const rd = document.getElementById("reviewDamageModal");
    if (rd) rd.addEventListener("show.bs.modal", ev => {
      const d = modalRow(ev); if (!d) return;
      setText("#rdVehicle", d.plate + " (" + d.type + ")");
      setText("#rdDriver", d.driver); setText("#rdWhen", d.when);
      setText("#rdNature", d.nature); setText("#rdParts", d.parts);
      const att = document.getElementById("rdAttachment");
      if (att) att.innerHTML = d.attachment === "true"
        ? '<span class="badge bg-primary bg-opacity-10 text-primary"><i class="bi bi-image me-1"></i>Photo attached</span>'
        : '<span class="text-secondary small">None</span>';
    });
  }

  function wireRepairModals() {
    if (!document.getElementById("rows-repairs")) return;
    const er = document.getElementById("editRepairModal");
    if (er) er.addEventListener("show.bs.modal", ev => {
      const d = modalRow(ev); if (!d) return;
      setVal("#erVehicle", d.plate + " (" + d.type + ")"); setVal("#erDriver", d.driver);
      setVal("#erDate", d.date); setVal("#erScope", d.scope);
      setVal("#erParts", d.parts.indexOf("None") === 0 ? "" : d.parts);
      setVal("#erCost", d.cost === "—" ? "" : d.cost);
      setVal("#erRemarks", d.remarks);
      selectByText("#erSource", d.source);
      setVal("#erShop", d.shop || "");
      toggleSpecify("#erSource", "#erShopWrap", "External Repair Shop");
    });
    const us = document.getElementById("updateStatusModal");
    if (us) us.addEventListener("show.bs.modal", ev => {
      const d = modalRow(ev); if (!d) return;
      setText("#usVehicle", d.plate + " (" + d.type + ")");
      setText("#usMeta", d.driver + " · Last repair: " + d.date);
      const b = document.getElementById("usBadge");
      if (b && d.badge) { b.className = "badge status-badge " + d.badge + " px-3 py-2 rounded-pill"; b.textContent = statusLabel(d.vstatus); }
    });
  }

  function wireDispatchModals() {
    const ed = document.getElementById("editDispatchModal");
    if (!ed) return;
    ed.addEventListener("show.bs.modal", ev => {
      const d = modalRow(ev); if (!d) return;
      setText("#edVehicle", d.plate + " (" + d.type + ")");
      setText("#edDriver", d.driver);
      const b = document.getElementById("edStatusBadge");
      if (b) { const active = d.status === "Active"; b.className = "badge " + (active ? "bg-primary" : "bg-secondary") + " px-3 py-2 rounded-pill"; b.textContent = d.status; }
      selectByText("#edMission", d.mission);
      setVal("#edMissionOther", d.missionOther || "");
      toggleSpecify("#edMission", "#edMissionOtherWrap", "Others");
      setVal("#edLocation", d.location);
      setVal("#edTimeOut", d.out);
      setVal("#edTimeIn", d.in || "");
      const wrap = document.getElementById("edTimeInWrap");
      if (wrap) wrap.style.display = d.in ? "" : "none";
    });
  }
  // Matches an option by exact text, or by prefix so "Others" selects the
  // "Others (Specify)" option used in the dispatch mission dropdowns.
  function selectByText(sel, text) {
    const el = document.querySelector(sel);
    if (el) Array.prototype.forEach.call(el.options, o => {
      if (o.text === text || o.text.indexOf(text + " (") === 0) o.selected = true;
    });
  }

  /* -------------------- frequently reported issues --------------------- */
  // Aggregated recurring BLOWBAGETS / damage findings for the agency
  // (Inspection Monitoring — plan §6.4). Ranked by occurrence count.
  function renderFrequentIssues() {
    const el = document.getElementById("freq-issues");
    if (!el) return;
    const items = (A.frequentIssues || []).slice().sort((a, b) => b.count - a.count);
    if (!items.length) { el.innerHTML = '<div class="text-secondary small">No recurring issues recorded.</div>'; return; }
    const max = items[0].count;
    el.innerHTML = items.map((it, idx) => {
      const pct = Math.round((it.count / max) * 100);
      return '<div class="d-flex align-items-center gap-3 py-2' + (idx ? ' border-top' : '') + '">' +
        '<span class="text-secondary fw-bold" style="min-width:1.5rem;">#' + (idx + 1) + '</span>' +
        '<div class="flex-grow-1">' +
        '<div class="d-flex justify-content-between align-items-center">' +
        '<span class="fw-semibold text-dark">' + esc(it.issue) + '</span>' +
        '<span class="small text-secondary ms-2">Last: ' + esc(it.last) + '</span></div>' +
        '<div class="progress mt-1" style="height:6px;"><div class="progress-bar bg-warning" role="progressbar" style="width:' + pct + '%"></div></div>' +
        '</div>' +
        '<span class="badge bg-warning text-dark rounded-pill">' + it.count + '×</span>' +
        '</div>';
    }).join("");
  }

  /* --------------- conditional "specify" inputs (modals) --------------- */
  // Reveals a free-text field when a select reaches a trigger value, e.g.
  // dispatch Mission = "Others" or repair Source = "External Repair Shop".
  function toggleSpecify(selectSel, wrapSel, triggerValue) {
    const sel = document.querySelector(selectSel);
    const wrap = document.querySelector(wrapSel);
    if (!sel || !wrap) return;
    const opt = sel.options[sel.selectedIndex];
    const val = (sel.value || (opt && opt.text) || "");
    wrap.style.display = val.indexOf(triggerValue) === 0 ? "" : "none";
  }
  function wireConditionalSpecifiers() {
    const pairs = [
      ["#ndMission", "#ndMissionOtherWrap", "Others"],
      ["#edMission", "#edMissionOtherWrap", "Others"],
      ["#lrSource", "#lrShopWrap", "External Repair Shop"],
      ["#erSource", "#erShopWrap", "External Repair Shop"]
    ];
    pairs.forEach(([s, w, t]) => {
      const sel = document.querySelector(s);
      if (!sel) return;
      const apply = () => toggleSpecify(s, w, t);
      sel.addEventListener("change", apply);
      apply();
    });
  }

  /* ----------------------- report generation --------------------------- */
  const plateOf = opt => (opt || "").split(" ")[0];
  function reportSpec(type, f) {
    const vehOK = r => !f.plate || r.plate === f.plate;
    const drvOK = r => !f.driver || r.driver === f.driver;
    switch (type) {
      case "Inspection Records":
        return { cols: ["Date / Time", "Vehicle", "Driver", "Result", "Remarks", "Review"],
          rows: A.inspections.filter(i => vehOK(i) && drvOK(i)).map(i =>
            [i.date + " " + i.time, i.plate + " (" + vtypeOf(i.plate) + ")", i.driver, i.result, i.remarks, i.review]) };
      case "Damage & Defects":
        return { cols: ["Date / Time", "Vehicle", "Driver", "Nature of Damage", "Suspected Parts", "Photo", "Review"],
          rows: A.damage.filter(d => vehOK(d) && drvOK(d)).map(d =>
            [d.date + " " + d.time, d.plate + " (" + vtypeOf(d.plate) + ")", d.driver, d.nature, d.parts, d.attachment ? "Yes" : "None", d.review]) };
      case "Repair & Maintenance History":
        return { cols: ["Date", "Vehicle", "Driver", "Scope of Work", "Parts Replaced", "Cost", "Repair Source", "Vehicle Status"],
          rows: A.repairs.filter(r => vehOK(r) && drvOK(r)).map(r =>
            [r.date, r.plate + " (" + vtypeOf(r.plate) + ")", r.driver, r.scope, r.parts, r.cost, repairSourceLabel(r), statusLabel(r.vstatus)]) };
      case "Preventive Maintenance Records": {
        const active = A.pmActive.filter(vehOK).map(p => [p.plate + " (" + p.vtype + ")", p.target, p.schedType, p.dueMain, p.status]);
        const done = A.pmCompleted.filter(vehOK).map(p => [p.plate + " (" + p.vtype + ")", p.performed, "Completed Service", p.date + " · " + p.source, "Completed"]);
        return { cols: ["Vehicle", "Maintenance", "Type", "Due / Serviced", "Status"], rows: active.concat(done) };
      }
      case "Dispatch Logs":
        return { cols: ["Mission", "Vehicle", "Driver", "Location", "Time Out", "Time In", "Status"],
          rows: A.dispatch.filter(d => vehOK(d) && drvOK(d)).map(d =>
            [missionLabel(d), d.plate + " (" + vtypeOf(d.plate) + ")", d.driver, d.location,
             d.out.join(" "), d.in ? d.in.join(" ") : "—", d.status]) };
      case "Vehicle Status Summary":
        return { cols: ["Plate", "Type", "Make / Model", "Assigned Driver", "Mileage", "Status"],
          rows: A.vehicles.map(v => [v.plate, v.type, v.mm, v.driver, v.km, statusLabel(v.status)]) };
      default:
        return { cols: [], rows: [] };
    }
  }
  function renderReport(type, f) {
    const out = document.getElementById("reportOutput");
    if (!out) return;
    const spec = reportSpec(type, f);
    const noFilter = type === "Vehicle Status Summary";
    const filterBits = [];
    if (!noFilter) {
      filterBits.push("Date range: " + f.range);
      filterBits.push("Vehicle: " + (f.plate || "All"));
      filterBits.push("Driver: " + (f.driver || "All"));
    } else {
      filterBits.push("Current snapshot of all vehicles");
    }
    const head = spec.cols.map(c => '<th>' + esc(c) + '</th>').join("");
    const body = spec.rows.length
      ? spec.rows.map(r => '<tr>' + r.map(c => '<td>' + esc(c) + '</td>').join("") + '</tr>').join("")
      : '<tr><td colspan="' + spec.cols.length + '" class="text-center text-secondary py-4">No records match the selected filters.</td></tr>';
    out.innerHTML =
      '<div id="reportPrintArea" class="card border-0 shadow-sm rounded-3 mb-4">' +
        '<div class="card-body p-4">' +
          '<div class="d-flex justify-content-between align-items-start border-bottom pb-3 mb-3">' +
            '<div class="d-flex align-items-center gap-3">' +
              '<img src="' + A.logo + '" alt="" class="agency-logo-img">' +
              '<div><div class="fw-bold fs-5" style="color:var(--primary);">' + esc(A.name) + '</div>' +
              '<div class="small text-secondary">Rescue Vehicle Management System · ' + esc(A.location) + '</div></div>' +
            '</div>' +
            '<div class="text-end"><div class="fw-bold">' + esc(type) + '</div>' +
            '<div class="small text-secondary">Generated: June 13, 2026</div></div>' +
          '</div>' +
          '<div class="small text-secondary mb-3">' + filterBits.map(esc).join(" &nbsp;·&nbsp; ") + '</div>' +
          '<div class="table-responsive"><table class="table table-sm table-bordered align-middle mb-0">' +
            '<thead class="table-light"><tr>' + head + '</tr></thead><tbody>' + body + '</tbody></table></div>' +
          '<div class="small text-secondary mt-3">' + spec.rows.length + ' record' + (spec.rows.length === 1 ? "" : "s") +
            ' · Report based solely on data encoded in the system.</div>' +
        '</div>' +
      '</div>' +
      '<div class="d-flex gap-2 mb-4 no-print">' +
        '<button class="btn bg-navy text-white" id="reportPrintBtn"><i class="bi bi-printer me-2"></i>Print / Save as PDF</button>' +
        '<button class="btn btn-light border" id="reportClearBtn">Clear</button>' +
      '</div>';
    out.scrollIntoView({ behavior: "smooth", block: "start" });
    const pb = document.getElementById("reportPrintBtn");
    if (pb) pb.addEventListener("click", () => window.print());
    const cb = document.getElementById("reportClearBtn");
    if (cb) cb.addEventListener("click", () => { out.innerHTML = ""; });
  }
  function wireReports() {
    const modal = document.getElementById("configureReportModal");
    if (!modal) return;
    let currentType = "Inspection Records";
    modal.addEventListener("show.bs.modal", ev => {
      const btn = ev.relatedTarget;
      if (btn && btn.dataset.reportType) currentType = btn.dataset.reportType;
      setText("#rpTitle", currentType);
      const noFilter = currentType === "Vehicle Status Summary";
      const fw = document.getElementById("rpFilters");
      if (fw) fw.style.display = noFilter ? "none" : "";
    });
    const gen = document.getElementById("rpGenerate");
    if (gen) gen.addEventListener("click", () => {
      const f = {
        range: (document.getElementById("rpRange") || {}).value || "All dates",
        plate: plateOf((document.getElementById("rpVehicle") || {}).value),
        driver: (document.getElementById("rpDriver") || {}).value || ""
      };
      if (f.plate.indexOf("All") === 0) f.plate = "";
      if (f.driver.indexOf("All") === 0) f.driver = "";
      renderReport(currentType, f);
      const inst = bootstrap.Modal.getInstance(modal);
      if (inst) inst.hide();
    });
  }

  /* ------------------------------ utils -------------------------------- */
  function setText(sel, val) { document.querySelectorAll(sel).forEach(el => el.textContent = val); }
  function setVal(sel, val) { const el = document.querySelector(sel); if (el) el.value = val; }

  /* ------------------------------ run ---------------------------------- */
  document.addEventListener("DOMContentLoaded", function () {
    renderChrome();
    renderBell();
    renderVehicles();
    renderDrivers();
    renderInspections();
    renderDamage();
    renderChecklistExtra();
    renderPM();
    renderDispatch();
    renderRepairs();
    renderDashboard();
    renderNotificationsPage();
    renderFrequentIssues();
    renderOptionLists();
    wireDriverModals();
    wireReviewModals();
    wireRepairModals();
    wireDispatchModals();
    wireConditionalSpecifiers();
    wireReports();
    decorateLinks();
  });

  // expose for quick console checks
  window.RVMS = { key: KEY, agency: A, all: AGENCIES };
})();
