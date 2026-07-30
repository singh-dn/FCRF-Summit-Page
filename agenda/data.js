/* =====================================================================
   FutureCrime Summit 2026 — all page content lives in this one file.

   There is no database and no admin panel. Edit this file, save it,
   upload it, done.

   ---------------------------------------------------------------------
   ADDING A PHOTO
   ---------------------------------------------------------------------
   1. Put the image in  images/speakers/
   2. Find the person below and fill in their photo line:

        photo: "images/speakers/rakshit-tandon.jpg"

   Square images look best. Anyone left with photo: "" shows a coloured
   circle with their initials instead, which is by design — a half-filled
   list still looks finished.

   ---------------------------------------------------------------------
   ADDING A SPEAKER
   ---------------------------------------------------------------------
   Add them to SPEAKERS with a short unique key, then put that key in the
   speakers list of whichever session they are in.

   ---------------------------------------------------------------------
   ADDING A SESSION
   ---------------------------------------------------------------------
   Copy any block in SESSIONS and change the values. Order does not
   matter — the page sorts by day and start time on its own.
   ===================================================================== */


/* ---------------------------------------------------------------------
   EVENT
   --------------------------------------------------------------------- */
const EVENT = {
  name:  "FutureCrime Summit 2026",
  venue: "Bharat Mandapam, Pragati Maidan, New Delhi",
};

const DAYS = [
  { day: 1, date: "2026-08-06", label: "Day 1" },
  { day: 2, date: "2026-08-07", label: "Day 2" },
];

const HALLS = [
  { name: "Main Hall",       colour: "#0ea5e9", note: "" },
  { name: "Innovation Hall", colour: "#64748b", note: "" },
];


/* ---------------------------------------------------------------------
   SPEAKERS
   Key -> details. The key is only used to link a person to a session.
   --------------------------------------------------------------------- */
const SPEAKERS = {

  /* Speaking at a session */
  "rajesh-pant":               { name: "Lt. Gen. Rajesh Pant",
                                designation: "Chairman, Cyber Security Association of India",
                                photo: "" },
  "sanjay-bahl":               { name: "Dr. Sanjay Bahl",
                                designation: "Director General, CERT-In",
                                photo: "" },
  "ashok-kumar":               { name: "Ashok Kumar",
                                designation: "Retired DGP",
                                photo: "" },
  "rajiv-jain":                { name: "Rajiv Jain",
                                designation: "Former Director, Intelligence Bureau",
                                photo: "" },
  "megha-khetarpal":           { name: "Megha Khetarpal",
                                designation: "TransUnion",
                                photo: "" },
  "mini-rani-sharma":          { name: "Mini Rani Sharma",
                                designation: "Head SeMT, MeitY",
                                photo: "" },
  "devesh-vatsa":              { name: "Air Vice Marshal (Dr) Devesh Vatsa VSM",
                                designation: "Advisor, DSCI NASSCOM",
                                photo: "" },
  "somen-das":                 { name: "Somen Das",
                                designation: "Associate Director, Accenture Cybersecurity",
                                photo: "" },
  "nishant-singh":             { name: "Lt. Col. Nishant Singh",
                                designation: "Chief Operating Officer, GRAMAX",
                                photo: "" },
  "naveen-jakhar":             { name: "Naveen Jakhar",
                                designation: "Director (AI & Digital Intelligence Unit) Department of Telecommunications ( DOT )",
                                photo: "" },
  "dinesh-o-bareja":           { name: "Dinesh O Bareja",
                                designation: "Cybersecurity Consultant",
                                photo: "" },
  "ashutosh-bahuguna":         { name: "Ashutosh Bahuguna",
                                designation: "CERT-In",
                                photo: "" },
  "deepak-singh":              { name: "Prof. (Dr.) Deepak Singh",
                                designation: "Associate Professor, IIIT, Lucknow",
                                photo: "" },
  "preeti-singh":              { name: "Preeti Singh",
                                designation: "Group CISO at Teciem",
                                photo: "" },
  "rakshit-tandon":            { name: "Dr. Rakshit Tandon",
                                designation: "Consultant - Cyber Crime HQ, UP Police; International Cyber Expert",
                                photo: "" },
  "ramkumari-harisankar-iyer": { name: "Dr Ramkumari Harisankar Iyer",
                                designation: "CIO & AI Security Governance Director, Reliscale Consulting Pvt. Ltd.",
                                photo: "" },
  "shishir-sarkar":            { name: "Shishir Sarkar",
                                designation: "Enterprise Architect, Vice President, Deutsche Bank",
                                photo: "" },
  "deepak-vatsa":              { name: "Deepak Vatsa",
                                designation: "HDFC ERGO",
                                photo: "" },
  "bharat-jeswani":            { name: "CA Bharat Jeswani",
                                designation: "FCA, CFE, CFCS, CAMS - Founder, AML Consultancy",
                                photo: "" },
  "durgesh-pandey":            { name: "CA Durgesh Pandey",
                                designation: "Managing Partner, DKMS and Associates; Honorary Professor, University of Portsmouth, UK",
                                photo: "" },
  "uday-kulkarni":             { name: "CA Uday Kulkarni",
                                designation: "Practising Chartered Accountant",
                                photo: "" },
  "gyan-barah":                { name: "Mr. Gyan Barah",
                                designation: "Senior Advisor, Jio Financial Services",
                                photo: "" },
  "soham-shah":                { name: "Soham Shah",
                                designation: "Founder and CEO, Yellow Stone Solutions",
                                photo: "" },
  "dubashish":                 { name: "Dubashish",
                                designation: "HDFC",
                                photo: "" },
  "aman-bandvi":               { name: "Aman Bandvi",
                                designation: "Founder, Director - Bharat Responsible AI Forum",
                                photo: "" },
  "piyush-kaushik":            { name: "Piyush Kaushik",
                                designation: "Product Manager, Forensics at Exterro",
                                photo: "" },
  "mfilterit":                 { name: "mFilterIt",
                                designation: "",
                                photo: "" },
  "jignesh-suba":              { name: "Jignesh Suba",
                                designation: "MH Service",
                                photo: "" },
  "abhinav-saurabh":           { name: "Abhinav Saurabh",
                                designation: "Strategic Advisor, Confidential",
                                photo: "" },
  "alok-gupta":                { name: "Alok Gupta",
                                designation: "",
                                photo: "" },
  "starlight":                 { name: "Starlight",
                                designation: "",
                                photo: "" },
  "innefu":                    { name: "Innefu",
                                designation: "",
                                photo: "" },
  "kumar-aniket":              { name: "Kumar Aniket",
                                designation: "",
                                photo: "" },
  "sainath-volam":             { name: "Sainath Volam",
                                designation: "",
                                photo: "" },
  "ajay-kanth-abc":            { name: "Ajay Kanth ABC",
                                designation: "",
                                photo: "" },
  "navaneethan-m":             { name: "Dr. Navaneethan M",
                                designation: "Chairman, CXO Cywayz",
                                photo: "" },
  "sanjay-kaushik":            { name: "Sanjay Kaushik",
                                designation: "",
                                photo: "" },
  "bharat-panchal":            { name: "Bharat Panchal",
                                designation: "",
                                photo: "" },
  "rajan-kochhar":             { name: "Major General Dr Rajan Kochhar, VSM",
                                designation: "",
                                photo: "" },
  "alok-vijayant":             { name: "Alok Vijayant",
                                designation: "",
                                photo: "" },
  "gulshan-rai":               { name: "Dr. Gulshan Rai",
                                designation: "NCSC; Former DG, CERT-In",
                                photo: "" },
  "pavan-duggal":              { name: "Pavan Duggal",
                                designation: "",
                                photo: "" },
  "rakesh-maheshwari":         { name: "Rakesh Maheshwari",
                                designation: "Advisor, Cyber Laws and Tech Policy; Former Sr. Director and GC, Cyber Law & Data Governance, MeitY",
                                photo: "" },
  "arun-kumar":                { name: "Arun Kumar",
                                designation: "",
                                photo: "" },
  "amit-sharma":               { name: "Amit Sharma",
                                designation: "DRDO",
                                photo: "" },
  "jeetendra-mishra":          { name: "Air Marshal Jeetendra Mishra",
                                designation: "",
                                photo: "" },
  "deep-pal-singh":            { name: "Deep Pal Singh",
                                designation: "Chief Risk Officer, Aditya Birla Capital",
                                photo: "" },
  "balaji-kapsikar":           { name: "Balaji Kapsikar",
                                designation: "",
                                photo: "" },
  "utsav-mittal":              { name: "Utsav Mittal",
                                designation: "",
                                photo: "" },
  "qnu-labs":                  { name: "QNu Labs",
                                designation: "",
                                photo: "" },
  "amit-dubey":                { name: "Amit Dubey",
                                designation: "",
                                photo: "" },
  "talwant-singh":             { name: "Justice Talwant Singh",
                                designation: "High Court Judge",
                                photo: "" },
  "pk-khosla":                 { name: "Dr. PK Khosla",
                                designation: "Ex DRDO",
                                photo: "" },
  "smith-company":             { name: "Smith & Company",
                                designation: "",
                                photo: "" },
  "paakhhi-garg":              { name: "Ms. Paakhhi Garg",
                                designation: "Founder, World Cybersecurity Forum",
                                photo: "" },
  "garima-goswamy":            { name: "Ms. Garima Goswamy",
                                designation: "Principal Risk Advisor, Inquest Global",
                                photo: "" },
  "sampurna":                  { name: "Sampurna",
                                designation: "",
                                photo: "" },
  "tanmayee-tilekar":          { name: "Tanmayee Tilekar",
                                designation: "Cybersecurity Expert",
                                photo: "" },
  "smita-mitra":               { name: "Smita Mitra",
                                designation: "INTERPOL, UN",
                                photo: "" },
  "shonal-d":                  { name: "Shonal D",
                                designation: "Anti-Cybercrime Strategist, AI & Cyber Psychologist",
                                photo: "" },
  "mimansa-ambastha":          { name: "Mimansa Ambastha",
                                designation: "Founder, Starlex Consultancy",
                                photo: "" },
  "divyam-agarwal":            { name: "Divyam Agarwal",
                                designation: "Asso. Partner, JSA",
                                photo: "" },
  "ashok-tarachand-ukrani":    { name: "Ashok Tarachand ukrani",
                                designation: "Gujarat DJ (Judge)",
                                photo: "" },
  "mahak-rathee":              { name: "Mahak Rathee",
                                designation: "Advocate-on-Record, Supreme Court",
                                photo: "" },
  "krishan-kumar":             { name: "Krishan Kumar",
                                designation: "Associate VP & Senior Legal Counsel, AI & Data Security",
                                photo: "" },
  "vibhav-mittal":             { name: "Vibhav Mittal",
                                designation: "Anand and Anand; Associate Partner │ Litigation",
                                photo: "" },
  "kulbhhushan-upadhyay":      { name: "Kulbhhushan Upadhyay",
                                designation: "Assistant General Manager, Telecommunications Consultants India Ltd.",
                                photo: "" },

  /* Not yet allocated to a session — they do not appear on the page
     until you add their key to one of the sessions below */
  "speaker":                { name: "---",
                             designation: "---",
                             photo: "" },
  "ashok-ukrani":           { name: "Ashok Ukrani",
                             designation: "Gujarat DJ",
                             photo: "" },
  "vinit-goenka":           { name: "Vinit Goenka",
                             designation: "Former BJP IT Cell",
                             photo: "" },
  "vijayant-gaur":          { name: "Vijayant Gaur",
                             designation: "",
                             photo: "" },
  "brijesh-singh":          { name: "Brijesh Singh",
                             designation: "IPS",
                             photo: "" },
  "venkatesh":              { name: "Venkatesh",
                             designation: "DSCI",
                             photo: "" },
  "manoj-abrahan":          { name: "Manoj Abrahan",
                             designation: "IPS, Kerala",
                             photo: "" },
  "loknath-behra":          { name: "Loknath Behra",
                             designation: "Former IPS",
                             photo: "" },
  "rahul-sharma":           { name: "Rahul Sharma",
                             designation: "The Perspective",
                             photo: "" },
  "rabindra-narayan-behra": { name: "Dr. Rabindra Narayan Behra",
                             designation: "MP, Lok Sabha",
                             photo: "" },
  "bipin-bakshi":           { name: "Maj Gen Dr Bipin Bakshi",
                             designation: "",
                             photo: "" },
  "smith-gonsalves":        { name: "Smith Gonsalves",
                             designation: "National Security, Information Warfare and Cognitive Manipulation",
                             photo: "" },
  "daljit-singh":           { name: "Air Marshal Daljit Singh",
                             designation: "",
                             photo: "" },
  "harsh-kumar":            { name: "Brig Harsh Kumar",
                             designation: "Operational Information Systems and Data Centricity in Defence",
                             photo: "" },
  "yaseer-hacker":          { name: "Yaseer Hacker",
                             designation: "Critical Infrastructure Security",
                             photo: "" },
  "ajay-kumar":             { name: "Prof. Ajay Kumar",
                             designation: "",
                             photo: "" },
  "vinny-sharma":           { name: "Vinny Sharma",
                             designation: "Galgotia",
                             photo: "" },
  "sampat-meena":           { name: "Sampat Meena",
                             designation: "",
                             photo: "" },
  "ram-kinkar":             { name: "Ram Kinkar",
                             designation: "",
                             photo: "" },
  "krishan-berwal":         { name: "Dr. Krishan Berwal",
                             designation: "",
                             photo: "" },
  "rekha-singh":            { name: "Rekha Singh",
                             designation: "",
                             photo: "" },
  "akansha-gupta":          { name: "Dy SP Akansha Gupta",
                             designation: "",
                             photo: "" },
  "pawan-anand":            { name: "Retd Maj Gen Pawan Anand",
                             designation: "Director, USI India",
                             photo: "" },
  "shashi-jha":             { name: "Shashi Jha",
                             designation: "Compliance, WazirX",
                             photo: "" },
};


/* ---------------------------------------------------------------------
   SESSIONS
   type: inauguration | panel | keynote | workshop | fireside |
         sponsor | valedictory | award | lunch | break | networking
   hall: "Main Hall", "Innovation Hall", or null for venue-wide
   --------------------------------------------------------------------- */
const SESSIONS = [

  /* ================= DAY 1 ================= */
  {
    day: 1, start: "09:30", end: "11:00",
    hall: "Main Hall", type: "inauguration",
    title: "Future Crime, National Security & Policy Roadmap: Building India's Resilient Digital Future",
    speakers: ["rajesh-pant", "sanjay-bahl", "ashok-kumar", "rajiv-jain"],
  },
  {
    day: 1, start: "11:00", end: "11:40",
    hall: "Main Hall", type: "panel",
    title: "Crime at Machine Speed: AI-Powered Cybercrime and the New Threat Landscape",
    speakers: ["megha-khetarpal", "mini-rani-sharma", "devesh-vatsa", "somen-das"],
  },
  {
    day: 1, start: "11:40", end: "12:00",
    hall: "Main Hall", type: "sponsor",
    title: "Sponsor Session 1",
    speakers: [],
  },
  {
    day: 1, start: "12:00", end: "12:40",
    hall: "Main Hall", type: "panel",
    title: "Defending the Digital Backbone: AI, Critical Infrastructure and Cyber Resilience",
    speakers: [
      "nishant-singh",
      "naveen-jakhar",
      "dinesh-o-bareja",
      "ashutosh-bahuguna",
      "deepak-singh",
      "preeti-singh",
    ],
  },
  {
    day: 1, start: "12:40", end: "13:00",
    hall: "Main Hall", type: "sponsor",
    title: "Sponsor Session 2",
    speakers: [],
  },
  {
    day: 1, start: "13:00", end: "14:00",
    hall: null, type: "lunch",
    title: "Networking Lunch",
    speakers: [],
  },
  {
    day: 1, start: "14:00", end: "14:40",
    hall: "Main Hall", type: "panel",
    title: "Beyond Secure Banking: Cybersecurity, Digital Payments and the Future of Financial Fraud Prevention",
    speakers: [
      "rakshit-tandon",
      "ramkumari-harisankar-iyer",
      "shishir-sarkar",
      "deepak-vatsa",
    ],
  },
  {
    day: 1, start: "14:40", end: "15:20",
    hall: "Main Hall", type: "panel",
    title: "Follow the Money: Crypto Fraud, Financial Crime and AML/CFT Intelligence",
    speakers: ["bharat-jeswani", "durgesh-pandey", "gyan-barah", "soham-shah"],
    moderators: ["uday-kulkarni", "dubashish"],
  },
  {
    day: 1, start: "15:20", end: "16:00",
    hall: "Main Hall", type: "panel",
    title: "From Data to Action: Predictive Policing, OSINT, Dark Web and AI-Led Investigation",
    speakers: ["aman-bandvi", "piyush-kaushik", "mfilterit"],
  },
  {
    day: 1, start: "16:00", end: "16:40",
    hall: "Innovation Hall", type: "panel",
    title: "AI-Powered Digital Forensics: Transforming Evidence Analysis and Criminal Investigation",
    speakers: ["jignesh-suba", "abhinav-saurabh", "alok-gupta", "starlight", "innefu"],
  },
  {
    day: 1, start: "16:40", end: "17:20",
    hall: "Innovation Hall", type: "panel",
    title: "The Cyber Compliance Mandate: Aligning Security Audits with RBI, SEBI and CERT-In Requirements",
    speakers: [
      "kumar-aniket",
      "sainath-volam",
      "ajay-kanth-abc",
      "navaneethan-m",
      "sanjay-kaushik",
      "bharat-panchal",
    ],
  },

  /* ================= DAY 2 ================= */
  {
    day: 2, start: "09:30", end: "11:00",
    hall: "Main Hall", type: "inauguration",
    title: "The New Theatre of Conflict: Cyber Warfare, Geopolitical Rivalries and National Security",
    speakers: [
      "rajan-kochhar",
      "alok-vijayant",
      "gulshan-rai",
      "pavan-duggal",
      "rakesh-maheshwari",
      "arun-kumar",
      "amit-sharma",
      "devesh-vatsa",
      "jeetendra-mishra",
      "deep-pal-singh",
    ],
  },
  {
    day: 2, start: "11:00", end: "11:40",
    hall: "Main Hall", type: "panel",
    title: "Beyond Encryption: Quantum-Safe Security and Future Tech-Crimes",
    speakers: [
      "balaji-kapsikar",
      "utsav-mittal",
      "qnu-labs",
      "amit-dubey",
      "talwant-singh",
      "pk-khosla",
    ],
  },
  {
    day: 2, start: "11:40", end: "12:20",
    hall: "Innovation Hall", type: "panel",
    title: "Digital Evidence Without Borders: Tackling Cross-Border Cybercrime Through International Cooperation",
    speakers: ["smith-company"],
  },
  {
    day: 2, start: "12:20", end: "13:00",
    hall: null, type: "sponsor",
    title: "Company Sponsor Slot",
    speakers: [],
  },
  {
    day: 2, start: "13:00", end: "14:00",
    hall: null, type: "lunch",
    title: "Networking Lunch",
    speakers: [],
  },
  {
    day: 2, start: "14:00", end: "14:40",
    hall: "Main Hall", type: "panel",
    title: "Protecting the Vulnerable: Child Safety, Cyberstalking and Online Victim Protection & CSAM",
    speakers: [
      "paakhhi-garg",
      "garima-goswamy",
      "sampurna",
      "tanmayee-tilekar",
      "smita-mitra",
      "shonal-d",
    ],
  },
  {
    day: 2, start: "14:40", end: "15:20",
    hall: "Main Hall", type: "panel",
    title: "From Privacy to Responsible AI: DPDP Act Compliance, Data Protection and AI Governance",
    speakers: [
      "mimansa-ambastha",
      "divyam-agarwal",
      "ashok-tarachand-ukrani",
      "mahak-rathee",
      "krishan-kumar",
      "vibhav-mittal",
    ],
  },
  {
    day: 2, start: "15:20", end: "16:00",
    hall: "Main Hall", type: "panel",
    title: "The Next Evidence Frontier: Cloud, Drone, IoT, Vehicle and Location Forensics",
    speakers: ["kulbhhushan-upadhyay"],
  },
  {
    day: 2, start: "16:00", end: "16:40",
    hall: "Main Hall", type: "panel",
    title: "TBD",
    speakers: [],
  },

  /* ================= INNOVATION HALL =================
     Space left for the Track 2 programme. Uncomment a block and fill it
     in, or send me the details and I will add them.

  { day: 1, start: "10:30", end: "11:00", hall: "Innovation Hall",
    type: "workshop",
    title: "",
    speakers: [] },

  { day: 2, start: "10:30", end: "11:00", hall: "Innovation Hall",
    type: "workshop",
    title: "",
    speakers: [] },

  =================================================== */
];


/* =====================================================================
   Below this line nothing needs editing — it just assembles the data
   into the shape the page expects.
   ===================================================================== */
(function () {
  const halls = HALLS.map((h, i) => ({
    id: i + 1, name: h.name, venue: EVENT.venue,
    floor_info: null, color_hex: h.colour, map_note: h.note || null,
  }));
  const hallId = Object.fromEntries(halls.map(h => [h.name, h.id]));

  const days = DAYS.map(d => ({
    id: d.day, day_number: d.day, event_date: d.date,
    label: d.label, subtitle: null,
  }));

  const ids = {};
  let n = 0;
  Object.keys(SPEAKERS).forEach(k => { ids[k] = ++n; });

  const person = (key, role) => {
    const s = SPEAKERS[key];
    if (!s) { console.warn("Unknown speaker key:", key); return null; }
    return {
      id: ids[key], name: s.name, honorific: null,
      designation: s.designation || null, organisation: null,
      bio: s.bio || null, photo: s.photo || null,
      linkedin: s.linkedin || null, role: role,
    };
  };

  const sessions = SESSIONS
    .filter(s => s.title)
    .sort((a, b) => (a.day - b.day) || String(a.start).localeCompare(String(b.start)))
    .map((s, i) => ({
      id: i + 1,
      day_id: s.day,
      hall_id: s.hall ? (hallId[s.hall] || null) : null,
      track_id: null,
      title: s.title,
      subtitle: s.subtitle || null,
      description: s.description || null,
      session_type: s.type || "panel",
      start_time: (s.start || "") + ":00",
      end_time: s.end ? s.end + ":00" : null,
      is_featured: 0,
      sort_order: (i + 1) * 10,
      speakers: [
        ...(s.moderators || []).map(k => person(k, "moderator")),
        ...(s.speakers || []).map(k => person(k, "panelist")),
      ].filter(Boolean),
    }));

  // Only people actually on the programme appear in the directory.
  const onStage = new Set();
  sessions.forEach(s => s.speakers.forEach(p => onStage.add(p.id)));

  const directory = Object.keys(SPEAKERS)
    .filter(k => onStage.has(ids[k]))
    .map(k => ({
      id: ids[k], full_name: SPEAKERS[k].name, honorific: null,
      designation: SPEAKERS[k].designation || null, organisation: null,
      bio: SPEAKERS[k].bio || null, photo_path: SPEAKERS[k].photo || null,
      linkedin_url: SPEAKERS[k].linkedin || null,
      category: null, is_featured: 0,
    }))
    .sort((a, b) => a.full_name.localeCompare(b.full_name));

  window.FCS = {
    event: EVENT, days, halls, tracks: [],
    sessions, speakers: directory,
  };
})();
