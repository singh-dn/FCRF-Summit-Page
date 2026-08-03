/* =====================================================================
   FutureCrime Summit 2026 — all page content lives in this one file.

   There is no database and no admin panel. Edit this file, save it,
   upload it, done.

   Rebuilt from:
     - Speaker Line-up and Agenda (Main Hall)
     - Track 2: Innovation Hall - Flexible Working Agenda

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

   Note: speakers are displayed in seniority order (government and
   defence first), not in the order listed here.
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
   HALLS TO HIDE FOR NOW

   A hall listed here keeps all of its sessions and speakers in this file,
   but does not appear on the page — no button, no cards, no speakers in
   the directory. Nothing is lost.

   To bring the Innovation Hall back, just empty the list:

       const HIDDEN_HALLS = [];

   --------------------------------------------------------------------- */
const HIDDEN_HALLS = ["Innovation Hall"];


/* ---------------------------------------------------------------------
   SPEAKERS
   Key -> details. The key is only used to link a person to a session.
   --------------------------------------------------------------------- */
const SPEAKERS = {

  /* Speaking at a session */
  "rajesh-pant":                  { name: "Lt. Gen. Rajesh Pant",
                                   designation: "Chairman,CSAI & Former National Cybersecurity Coordinator",
                                   photo: "" },
  "sanjay-bahl":                  { name: "Dr. Sanjay Bahl",
                                   designation: "Director General, CERT-In",
                                   photo: "" },
  "ashok-kumar":                  { name: "Ashok Kumar",
                                   designation: "Former DGP, Uttrakhand Police",
                                   photo: "" },
  "jeetendra-mishra":             { name: "Air Marshal Jeetendra Mishra, SYSM, AVSM, VSM",
                                   designation: "Former Air Officer Commanding-in-Chief, Western Air Command",
                                   photo: "" },
  "rajiv-jain":                   { name: "Rajiv Jain",
                                   designation: "Former Director, Intelligence Bureau",
                                   photo: "" },
  "gulshan-rai":                  { name: "Dr. Gulshan Rai",
                                   designation: "Former Director, CERT-In & Former National Cybersecurity Coordinator",
                                   photo: "" },
  "megha-khetarpal":              { name: "Megha Khetarpal",
                                   designation: "Senior Director, Fraud & Identity Global Products, TransUnion",
                                   photo: "" },
  "mini-rani-sharma":             { name: "Mini Rani Sharma",
                                   designation: "Head SeMT, MeitY",
                                   photo: "" },
  "pavan-duggal":                 { name: "Dr. Pavan Duggal",
                                   designation: "Advocate, Supreme Court of India & President of Cyberlaws.Net",
                                   photo: "" },
  "somen-das":                    { name: "Somen Das",
                                   designation: "Associate Director, Accenture Cybersecurity",
                                   photo: "" },
  "anand-aggarwal":               { name: "Anand Aggarwal",
                                   designation: "Group CIO Maxtel India (Creative Telecom)",
                                   photo: "" },
  "bipin-bakshi":                 { name: "Maj Gen Dr Bipin Bakshi",
                                   designation: "Distinguished Fellow at CLAWS",
                                   photo: "" },
  "nishant-singh":                { name: "Lt. Col. Nishant Singh",
                                   designation: "Chief Operating Officer, GRAMAX",
                                   photo: "" },
  "naveen-jakhar":                { name: "Naveen Jakhar, ITS",
                                   designation: "Director (AI & Digital Intelligence Unit) Department of Telecommunications ( DOT )",
                                   photo: "" },
  "dinesh-o-bareja":              { name: "Dinesh O Bareja",
                                   designation: "Cybersecurity Consultant",
                                   photo: "" },
  "ashutosh-bahuguna":            { name: "Ashutosh Bahuguna",
                                   designation: "Scientist 'E', CERT-In",
                                   photo: "" },
  "deepak-singh":                 { name: "Prof. (Dr.) Deepak Singh",
                                   designation: "Associate Professor, IIIT, Lucknow",
                                   photo: "" },
  "aditya-varma":                 { name: "Cdr. Aditya Varma (Retd.)",
                                   designation: "Leader - Public Sector, Security (India & SAARC), CISCO",
                                   photo: "" },
  "rakshit-tandon":               { name: "Dr. Rakshit Tandon",
                                   designation: "Consultant - Cyber Crime HQ, UP Police; International Cyber Expert",
                                   photo: "" },
  "ramkumari-harisankar-iyer":    { name: "Dr Ramkumari Harisankar Iyer",
                                   designation: "CIO & AI Security Governance Director, Reliscale Consulting Pvt. Ltd.",
                                   photo: "" },
  "shishir-sarkar":               { name: "Shishir Sarkar",
                                   designation: "Enterprise Architect, Vice President, Deutsche Bank",
                                   photo: "" },
  "mehnaz-perveen":               { name: "Mehnaz Perveen",
                                   designation: "Vice President of Trust & Safety, LERT at Paytm",
                                   photo: "" },
  "divyam-agarwal":               { name: "Divyam Agarwal",
                                   designation: "Associate Partner, JSA",
                                   photo: "" },
  "aarun-shankar-chandrasekaran": { name: "Aarun Shankar Chandrasekaran",
                                   designation: "Head - Risk & Compliance, Aditya Birla Capital Digital",
                                   photo: "" },
  "bharat-jeswani":               { name: "CA Bharat Jeswani",
                                   designation: "FCA, CFE, CFCS, CAMS - Founder, AML Consultancy",
                                   photo: "" },
  "bibhu-patnaik":                { name: "Bibhu Patnaik",
                                   designation: "Editor & Writer - TheStreet, TechBullion, MSN, New York Post, Nonce Media",
                                   photo: "" },
  "gyan-barah":                   { name: "Gyan Barah",
                                   designation: "Senior Advisor, Jio Financial Services",
                                   photo: "" },
  "soham-shah":                   { name: "Soham Shah",
                                   designation: "Founder and CEO, Yellow Stone Solutions",
                                   photo: "" },
  "aman-bandvi":                  { name: "Aman Bandvi",
                                   designation: "Founder, Director - Bharat Responsible AI Forum",
                                   photo: "" },
  "piyush-kaushik":               { name: "Piyush Kaushik",
                                   designation: "Product Manager, Forensics at Exterro",
                                   photo: "" },
  "varun-grover":                 { name: "Varun Grover",
                                   designation: "Business Unit Head for Brand Monitoring and Risk, mFilterIt",
                                   photo: "" },
  "aditya-ps":                    { name: "Aditya PS",
                                   designation: "Founder, TerraEagle",
                                   photo: "" },
  "yasir-arafat-shaikh":          { name: "Yasir Arafat Shaikh",
                                   designation: "Critical Infrastructure Security",
                                   photo: "" },
  "kumar-aniket":                 { name: "Kumar Aniket",
                                   designation: "Legal expert in technology law.",
                                   photo: "" },
  "vinit-goenka":                 { name: "Vinit Goenka",
                                   designation: "Secretary, Centre for Knowledge Sovereignty",
                                   photo: "" },
  "ajay-kanth":                   { name: "Ajay Kanth",
                                   designation: "Head - Fraud Risk Management Unit, Aditya Birla Capital",
                                   photo: "" },
  "sanjay-kaushik":               { name: "Sanjay Kaushik",
                                   designation: "CEO, Netrika Consultancy",
                                   photo: "" },
  "bharat-panchal":               { name: "Bharat Panchal",
                                   designation: "Chief Risk & Regulatory Officer - APAC and Middle East, Global Payment Network, Discover (CapitalOne)",
                                   photo: "" },
  "jignesh-suba":                 { name: "Jignesh Suba",
                                   designation: "CEO - South Asia, mH Service",
                                   photo: "" },
  "abhinav-saurabh":              { name: "Abhinav Saurabh",
                                   designation: "Cyber Forensics Expert",
                                   photo: "" },
  "alok-gupta":                   { name: "Alok Gupta",
                                   designation: "CEO, Secure Operations & AI",
                                   photo: "" },
  "shailesh-singh":               { name: "Shailesh Singh",
                                   designation: "CEO & Director, Starlight Data Solutions",
                                   photo: "" },
  "tarun-wig":                    { name: "Tarun Wig",
                                   designation: "Co-Founder, Innefu",
                                   photo: "" },
  "rajan-kochhar":                { name: "Maj. Gen. Dr Rajan Kochhar, VSM",
                                   designation: "Advisory Board Member NITI - The Policy Lab, Kirori Mal College",
                                   photo: "" },
  "alok-vijayant":                { name: "Dr. Alok Vijayant",
                                   designation: "Former Director, Cyber Security Operations, Govt of India, Founder - SciRoIT Technologies",
                                   photo: "" },
  "sandeep-sharma":               { name: "Maj. Gen. (Dr) Sandeep Sharma (Retd.)",
                                   designation: "Senior Fellow CLAWS, ERF RRU, Ex Commander Army Cyber Group and Scientist H, Govt of India",
                                   photo: "" },
  "arun-kumar":                   { name: "Arun Kumar",
                                   designation: "Former DG, RPF",
                                   photo: "" },
  "amit-sharma":                  { name: "Dr. Amit Sharma",
                                   designation: "ADGl and Advisor (Cyber), Ministry of Defence, GoI",
                                   photo: "" },
  "devesh-vatsa":                 { name: "Air Vice Marshal (Dr) Devesh Vatsa VSM",
                                   designation: "Advisor, DSCI (NASSCOM)",
                                   photo: "" },
  "balaji-kapsikar":              { name: "Balaji Kapsikar",
                                   designation: "Sr Manager Cyber Security & Cyber Risk, DPO",
                                   photo: "" },
  "utsav-mittal":                 { name: "Utsav Mittal",
                                   designation: "CEO, Xiarch Bharat",
                                   photo: "" },
  "amit-kumar-chauhan":           { name: "Dr. Amit Kumar Chauhan",
                                   designation: "Senior Researcher in the Post-Quantum Cryptography (PQC) Research and Innovation Group at QNu Labs",
                                   photo: "" },
  "amit-dubey":                   { name: "Prof. Amit Dubey",
                                   designation: "Cyber Security Evangelist",
                                   photo: "" },
  "talwant-singh":                { name: "Justice Talwant Singh",
                                   designation: "Former Judge, Delhi High Court & Senior Advocate",
                                   photo: "" },
  "pk-khosla":                    { name: "Dr. PK Khosla",
                                   designation: "Pro Vice Chancellor, Chitkara University",
                                   photo: "" },
  "suhel-daud":                   { name: "Suhel Daud",
                                   designation: "Legal Attache FBI US Embassy",
                                   photo: "" },
  "smith-gonsalves":              { name: "Smith Gonsalves",
                                   designation: "National Security, Information Warfare and Cognitive Manipulation",
                                   photo: "" },
  "himadrish-suwan":              { name: "Himadrish Suwan",
                                   designation: "Chairman, Confederation of Young Leaders of India",
                                   photo: "" },
  "pawan-anand":                  { name: "Maj Gen (Dr) Pawan Anand, AVSM (Retd)",
                                   designation: "Director, USI of India (Centre for Emerging Tech for AtmaNirbhar Bharat)",
                                   photo: "" },
  "paakhhi-garg":                 { name: "Paakhhi Garg",
                                   designation: "Founder, World Cybersecurity Forum",
                                   photo: "" },
  "garima-goswamy":               { name: "Garima Goswamy",
                                   designation: "Principal Risk Advisor, Inquest Global",
                                   photo: "" },
  "sampurna":                     { name: "Sampurna",
                                   designation: "Executive Director of India Child Protection (ICP)",
                                   photo: "" },
  "tanmayee-tilekar":             { name: "Tanmayee Tilekar",
                                   designation: "Cybersecurity Expert",
                                   photo: "" },
  "smita-mitra":                  { name: "Smita Mitra",
                                   designation: "Former Criminal Intelligence Officer, INTERPOL",
                                   photo: "" },
  "shonal-d":                     { name: "Shonal D",
                                   designation: "Anti-Cybercrime Strategist, AI & Cyber Psychologist",
                                   photo: "" },
  "mimansa-ambastha":             { name: "Mimansa Ambastha",
                                   designation: "Founder, Starlex Consultancy",
                                   photo: "" },
  "deep-pal-singh":               { name: "Deep Pal Singh",
                                   designation: "Chief Risk Officer, Aditya Birla Capital",
                                   photo: "" },
  "ashok-tarachand-ukrani":       { name: "Ashok Tarachand Ukrani",
                                   designation: "Former District Judge, Gujarat Judiciary",
                                   photo: "" },
  "akhil-kumar-jha":              { name: "Akhil Kumar Jha",
                                   designation: "Director Delivery, GoTrust",
                                   photo: "" },
  "vibhav-mithal":                { name: "Vibhav Mithal",
                                   designation: "Anand and Anand; Associate Partner │ Litigation",
                                   photo: "" },
  "rakesh-maheshwari":            { name: "Rakesh Maheshwari",
                                   designation: "Advisor, Cyber Laws and Tech Policy; Former Sr. Director and GC, Cyber Law & Data Governance, MeitY",
                                   photo: "" },
  "kulbhushan-upadhyay":          { name: "Dr. Kulbhushan Upadhyay",
                                   designation: "Assistant General Manager, Telecommunications Consultants India Ltd.",
                                   photo: "" },
  "ranjeet-mishra":               { name: "Ranjeet Mishra",
                                   designation: "CEO, Centre of Excellence for Cybersecurity - Karnataka (CySecK)",
                                   photo: "" },
  "deepak-vatsa":                 { name: "Deepak Vatsa",
                                   designation: "Senior VP, Fraud Control, HDFC ERGO",
                                   photo: "" },
  "ankush-goyal":                 { name: "Col. Ankush Goyal",
                                   designation: "",
                                   photo: "" },
  "raj-kumar-mishra":             { name: "Raj Kumar Mishra",
                                   designation: "Addl. SP, STF, UP Police",
                                   photo: "" },
  "satish-kumar-gupta":           { name: "CA (Dr.) Satish Kumar Gupta",
                                   designation: "Head - Internal Audit & Assurance, Berar Finance",
                                   photo: "" },
  "sanjeev-bansal":               { name: "Prof. (Dr.) Sanjeev Bansal",
                                   designation: "Addl Pro Vice-chancellor, Dean of FMS & Director of ABS, Amity University",
                                   photo: "" },
  "rahul-sharma":                 { name: "Rahul Sharma",
                                   designation: "Founder, The Perspective",
                                   photo: "" },
  "arunabha-mukhopadhyay":        { name: "Prof. Arunabha Mukhopadhyay",
                                   designation: "Professor, IIM Lucknow",
                                   photo: "" },
  "navaneethan-m":                { name: "Navaneethan M",
                                   designation: "Chairman, CXO Cywayz",
                                   photo: "" },
  "i4c-mha":                      { name: "I4C MHA",
                                   designation: "",
                                   photo: "" },
  "akash-thakar":                 { name: "Dr Akash Thakar",
                                   designation: "Assistant Professor, Rashtriya Raksha University",
                                   photo: "" },
  "himanshu-patel":               { name: "Himanshu Patel",
                                   designation: "Senior Manager - Cyber Defence, Incident Response and Forensics, Protiviti",
                                   photo: "" },
  "malvika-mehta":                { name: "Malvika Mehta",
                                   designation: "Founder, BLK Coral Intelligence Pvt Ltd",
                                   photo: "" },
  "nishant-sawant":               { name: "Nishant Sawant",
                                   designation: "Director, Managed Security Services",
                                   photo: "" },
  "preeti-singh":                 { name: "Preeti Singh",
                                   designation: "Group CISO - Tiecem",
                                   photo: "" },
  "somesh":                       { name: "Somesh",
                                   designation: "Founder & CEO, PaladinA",
                                   photo: "" },
  "harpreet":                     { name: "Harpreet",
                                   designation: "",
                                   photo: "" },
  "deepak":                       { name: "Deepak (D3)",
                                   designation: "Sr Cyber Intelligence and Digital Forensics Professional",
                                   photo: "" },
  "sumit":                        { name: "Sumit (MH)",
                                   designation: "",
                                   photo: "" },
  "tarun-sharma":                 { name: "Tarun Sharma (MH)",
                                   designation: "",
                                   photo: "" },
  "ajay-sariyal":                 { name: "Ajay Sariyal",
                                   designation: "MSAB",
                                   photo: "" },
  "saurabh-haritesh":             { name: "Saurabh Haritesh",
                                   designation: "SI, Delhi Police",
                                   photo: "" },
  "aakash-verma":                 { name: "Aakash Verma",
                                   designation: "",
                                   photo: "" },
  "utkarsh-jain":                 { name: "Utkarsh Jain",
                                   designation: "",
                                   photo: "" },
  "sanskriti-grover":             { name: "Sanskriti Grover",
                                   designation: "",
                                   photo: "" },
  "raghuveer-kaur":               { name: "Dr. Raghuveer Kaur",
                                   designation: "DPO, Leading NBFC",
                                   photo: "" },
  "suditi-tandon":                { name: "Suditi Tandon",
                                   designation: "Senior Officer - Global Data Privacy Office Specialist, Corporate Legal & Compliance, Hella India Automotive Private Limited",
                                   photo: "" },
  "krishan-kumar":                { name: "Krishan Kumar",
                                   designation: "SPECTRA",
                                   photo: "" },
  "dipanshu-parashar":            { name: "Dipanshu Parashar",
                                   designation: "",
                                   photo: "" },
  "nirmal-raj-m":                 { name: "Nirmal Raj M",
                                   designation: "OnMeta",
                                   photo: "" },
  "mahak-rathee":                 { name: "Mahak Rathee",
                                   designation: "Advocate-on-Record, Supreme Court",
                                   photo: "" },
  "digvijay-singh-rathore":       { name: "Digvijay Singh Rathore",
                                   designation: "Assistant Professor, Veer Bahadur Singh Purvanchal University, Jaunpur, Uttar Pradesh",
                                   photo: "" },
  "kritika":                      { name: "Er. Kritika",
                                   designation: "",
                                   photo: "" },
  "vinny-sharma":                 { name: "Dr. (Mrs.) Vinny Sharma",
                                   designation: "Galgotia University",
                                   photo: "" },
  "ritwik-srivastava":            { name: "Ritwik Srivastava",
                                   designation: "SP City, Dhanbad",
                                   photo: "" },

  /* Not yet allocated to a session — they do not appear on the
     page until you add their key to one of the sessions below */
  "vijayant-gaur":                     { name: "Vijayant Gaur",
                                        designation: "",
                                        photo: "" },
  "rabindra-narayan-behra":            { name: "Dr. Rabindra Narayan Behra",
                                        designation: "MP, Lok Sabha",
                                        photo: "" },
  "daljit-singh":                      { name: "Air Marshal Daljit Singh",
                                        designation: "",
                                        photo: "" },
  "harsh-kumar":                       { name: "Brig Harsh Kumar",
                                        designation: "Operational Information Systems and Data Centricity in Defence",
                                        photo: "" },
  "ajay-kumar":                        { name: "Prof. Ajay Kumar",
                                        designation: "",
                                        photo: "" },
  "sampat-meena":                      { name: "Sampat Meena",
                                        designation: "",
                                        photo: "" },
  "ram-kinkar-singh":                  { name: "Dr. Ram Kinkar Singh",
                                        designation: "",
                                        photo: "" },
  "taiwan-guy":                        { name: "Taiwan guy",
                                        designation: "",
                                        photo: "" },
  "ranjeet-mishra-centre-head-cyseck": { name: "Ranjeet Mishra , Centre Head CySecK",
                                        designation: "TBA",
                                        photo: "" },
  "shashi-jha":                        { name: "Shashi Jha",
                                        designation: "Compliance, WazirX",
                                        photo: "" },
};


/* ---------------------------------------------------------------------
   SESSIONS
   type: inauguration | panel | keynote | workshop | fireside |
         sponsor | valedictory | award | announcement | lunch | break |
         networking
   hall: "Main Hall", "Innovation Hall", or null for venue-wide
   --------------------------------------------------------------------- */
const SESSIONS = [

  /* ================= DAY 1 ================= */
  {
    day: 1, start: "09:30", end: "10:50",
    hall: "Main Hall", type: "inauguration",
    title: "Future Crime, National Security & Policy Roadmap: Building India's Resilient Digital Future",
    speakers: [
      "rajesh-pant",
      "sanjay-bahl",
      "ashok-kumar",
      "jeetendra-mishra",
      "rajiv-jain",
      "gulshan-rai",
    ],
  },
  {
    day: 1, start: "10:50", end: "11:00",
    hall: "Main Hall", type: "announcement",
    title: "India’s Largest Bug Bounty Program Announcement",
    speakers: [],
  },
  {
    day: 1, start: "11:00", end: "11:40",
    hall: "Main Hall", type: "panel",
    title: "Crime at Machine Speed: AI-Powered Cybercrime and the New Threat Landscape",
    speakers: [
      "megha-khetarpal",
      "mini-rani-sharma",
      "pavan-duggal",
      "somen-das",
      "anand-aggarwal",
      "bipin-bakshi",
    ],
  },
  {
    day: 1, start: "11:00", end: "11:30",
    hall: "Innovation Hall", type: "workshop",
    title: "AI Investigation Presentation",
    speakers: ["i4c-mha"],
  },
  {
    day: 1, start: "11:30", end: "12:00",
    hall: "Innovation Hall", type: "panel",
    title: "Defending the Digital Backbone: AI-Powered Cybercrime, Critical Infrastructure, and Cyber Resilience",
    speakers: [
      "akash-thakar",
      "himanshu-patel",
      "malvika-mehta",
      "nishant-sawant",
      "preeti-singh",
    ],
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
      "aditya-varma",
    ],
  },
  {
    day: 1, start: "12:00", end: "12:30",
    hall: "Innovation Hall", type: "sponsor",
    title: "Sponsor Innovation Showcase 1 — Product Demonstration / Technology Presentation",
    speakers: [],
  },
  {
    day: 1, start: "12:30", end: "13:00",
    hall: "Innovation Hall", type: "panel",
    title: "Emerging Crimes in the AI Era: Investigation Challenges, Digital Forensics & Future-Ready Solutions",
    speakers: ["somesh", "harpreet", "deepak", "sumit", "tarun-sharma"],
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
    title: "Lunch Break",
    speakers: [],
  },
  {
    day: 1, start: "14:00", end: "14:40",
    hall: "Main Hall", type: "panel",
    title: "Beyond Secure BFSI: Cybersecurity, Digital Payments and the Future of Financial Fraud Prevention",
    speakers: [
      "rakshit-tandon",
      "ramkumari-harisankar-iyer",
      "shishir-sarkar",
      "mehnaz-perveen",
      "divyam-agarwal",
      "aarun-shankar-chandrasekaran",
    ],
  },
  {
    day: 1, start: "14:00", end: "14:30",
    hall: "Innovation Hall", type: "workshop",
    title: "How to Setup a Cyber Lab",
    speakers: [
      "ajay-sariyal",
      "abhinav-saurabh",
      "saurabh-haritesh",
      "aakash-verma",
      "utkarsh-jain",
      "sanskriti-grover",
    ],
  },
  {
    day: 1, start: "14:30", end: "15:00",
    hall: "Innovation Hall", type: "sponsor",
    title: "Sponsor Innovation Showcase 2 — Product Demonstration / Technology Presentation",
    speakers: [],
  },
  {
    day: 1, start: "14:40", end: "15:20",
    hall: "Main Hall", type: "panel",
    title: "Follow the Money: Crypto Fraud, Financial Crime and AML/CFT Intelligence",
    speakers: ["bharat-jeswani", "bibhu-patnaik", "gyan-barah", "soham-shah"],
  },
  {
    day: 1, start: "15:00", end: "15:30",
    hall: "Innovation Hall", type: "workshop",
    title: "Consent Management Platform under the DPDP Act: Live Demonstration",
    speakers: [],
  },
  {
    day: 1, start: "15:20", end: "16:00",
    hall: "Main Hall", type: "panel",
    title: "From Data to Action: Predictive Policing, OSINT, Dark Web and AI-Led Investigation",
    speakers: [
      "aman-bandvi",
      "piyush-kaushik",
      "varun-grover",
      "aditya-ps",
      "yasir-arafat-shaikh",
    ],
  },
  {
    day: 1, start: "16:00", end: "16:40",
    hall: "Main Hall", type: "panel",
    title: "The Cyber Compliance Mandate: Aligning Security Audits with RBI, SEBI and CERT-In Requirements",
    speakers: [
      "kumar-aniket",
      "vinit-goenka",
      "ajay-kanth",
      "sanjay-kaushik",
      "bharat-panchal",
    ],
  },
  {
    day: 1, start: "16:40", end: "17:20",
    hall: "Main Hall", type: "panel",
    title: "AI-Powered Digital Forensics: Transforming Evidence Analysis and Criminal Investigation",
    speakers: [
      "jignesh-suba",
      "abhinav-saurabh",
      "alok-gupta",
      "shailesh-singh",
      "tarun-wig",
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
      "sandeep-sharma",
      "arun-kumar",
      "amit-sharma",
      "devesh-vatsa",
    ],
  },
  {
    day: 2, start: "11:00", end: "11:40",
    hall: "Main Hall", type: "panel",
    title: "Beyond Encryption: Quantum-Safe Security and Future Tech-Crimes",
    speakers: [
      "balaji-kapsikar",
      "utsav-mittal",
      "amit-kumar-chauhan",
      "amit-dubey",
      "talwant-singh",
      "pk-khosla",
    ],
  },
  {
    day: 2, start: "11:00", end: "11:30",
    hall: "Innovation Hall", type: "workshop",
    title: "Cyber Range for Cyber Commandos: Live Attack-and-Defence Simulation",
    speakers: [],
  },
  {
    day: 2, start: "11:30", end: "12:00",
    hall: "Innovation Hall", type: "sponsor",
    title: "Sponsor Innovation Showcase 3 — Product Demonstration / Technology Presentation",
    speakers: [],
  },
  {
    day: 2, start: "11:40", end: "12:20",
    hall: "Main Hall", type: "panel",
    title: "The Future of Digital Peace: International Cooperation Against Emerging Cyber Threats",
    speakers: ["suhel-daud", "smith-gonsalves", "himadrish-suwan", "pawan-anand"],
  },
  {
    day: 2, start: "12:00", end: "12:30",
    hall: "Innovation Hall", type: "workshop",
    title: "TTEX – Cyber Crisis Exercise: Command, Coordination and Response Drill",
    speakers: [],
  },
  {
    day: 2, start: "12:20", end: "13:00",
    hall: "Main Hall", type: "sponsor",
    title: "Company Sponsor Slot",
    speakers: [],
  },
  {
    day: 2, start: "12:30", end: "13:00",
    hall: "Innovation Hall", type: "workshop",
    title: "Mobile and Cyber Forensics: Live Extraction and Evidence Analysis Demo",
    speakers: [],
  },
  {
    day: 2, start: "13:00", end: "14:00",
    hall: null, type: "lunch",
    title: "Lunch Break",
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
    day: 2, start: "14:00", end: "14:30",
    hall: "Innovation Hall", type: "sponsor",
    title: "Sponsor Innovation Showcase 4 — Product Demonstration / Technology Presentation",
    speakers: [],
  },
  {
    day: 2, start: "14:30", end: "15:00",
    hall: "Innovation Hall", type: "panel",
    title: "Data Fiduciary Accountability: Governance, Audits and the Role of Data Protection Officers in India",
    speakers: [
      "raghuveer-kaur",
      "suditi-tandon",
      "krishan-kumar",
      "dipanshu-parashar",
      "nirmal-raj-m",
    ],
  },
  {
    day: 2, start: "14:40", end: "15:20",
    hall: "Main Hall", type: "panel",
    title: "From Privacy to Responsible AI: DPDP Act Compliance, Data Protection and AI Governance",
    speakers: [
      "mimansa-ambastha",
      "deep-pal-singh",
      "ashok-tarachand-ukrani",
      "akhil-kumar-jha",
      "vibhav-mithal",
      "rakesh-maheshwari",
    ],
  },
  {
    day: 2, start: "15:00", end: "15:30",
    hall: "Innovation Hall", type: "sponsor",
    title: "Sponsor Innovation Showcase 5 — Product Demonstration / Technology Presentation",
    speakers: [],
  },
  {
    day: 2, start: "15:20", end: "16:00",
    hall: "Main Hall", type: "panel",
    title: "The Next Evidence Frontier: Cloud, Drone, IoT, Vehicle and Location Forensics",
    speakers: ["kulbhushan-upadhyay", "ranjeet-mishra", "deepak-vatsa", "ankush-goyal"],
  },
  {
    day: 2, start: "15:30", end: "16:00",
    hall: "Innovation Hall", type: "workshop",
    title: "Emerging Cyber Crimes, New Laws and Best Practices in Investigation",
    speakers: [
      "mahak-rathee",
      "digvijay-singh-rathore",
      "kritika",
      "vinny-sharma",
      "ritwik-srivastava",
    ],
  },
  {
    day: 2, start: "16:00", end: "16:40",
    hall: "Main Hall", type: "panel",
    title: "Future Policing, Digital Crime, Forensics & Technology Law: Shaping the Next Generation of Criminal Justice",
    speakers: [
      "raj-kumar-mishra",
      "satish-kumar-gupta",
      "sanjeev-bansal",
      "rahul-sharma",
      "arunabha-mukhopadhyay",
      "navaneethan-m",
    ],
  },
];


/* =====================================================================
   Below this line nothing needs editing — it just assembles the data
   into the shape the page expects.
   ===================================================================== */
(function () {
  const allHalls = HALLS.map((h, i) => ({
    id: i + 1, name: h.name, venue: EVENT.venue,
    floor_info: null, color_hex: h.colour, map_note: h.note || null,
  }));
  const hallId = Object.fromEntries(allHalls.map(h => [h.name, h.id]));

  // Ids the page should pretend do not exist, while the data stays put.
  const hidden = new Set(
    (typeof HIDDEN_HALLS !== "undefined" ? HIDDEN_HALLS : [])
      .map(n => hallId[n]).filter(Boolean)
  );
  const halls = allHalls.filter(h => !hidden.has(h.id));

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
    .filter(s => !(s.hall && hidden.has(hallId[s.hall])))
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
