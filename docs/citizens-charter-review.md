# Citizen’s Charter review — services per office

**Source of truth:** `plgu-benguet-citizens-charter-2026.pdf`, *Provincial
Government of Benguet Citizen’s Charter 2026 (1st Edition)*, 398 pages.
Reviewed 2026-10-02. Its table of contents is the same list as
`2026 services.docx`. The per-office Word files in `arta-offices/` are older
office drafts and were used only for comparison. Staff names in the charter
are not copied here.

Machine-readable copy of every service below:
[`data/charter-services-2026.json`](data/charter-services-2026.json).

## Decisions so far (2026-10-02)

- **Seeding source:** the 2026 charter (this file), as recommended.
- Add the four offices missing from the offices list: **OG-PESO**, **OG-BAC**,
  **OG-CAO**, **OG-SDO**.
- **OG-IT** and **OG-Records** stay as their own offices, because each office has
  its own CSMF.
- **OG-OPA** is the same office as Main (OG), and **OSMP** the same as **OSSP**
  (confirmed 2026-10-02, playbook Q5). Both duplicates are removed from the
  offices list, which leaves 34 offices.
- The `OG-*` offices sit under OG in the office hierarchy, OG-IT and
  OG-Records included (one level deep).
- The name matches between the two documents (playbook 1.6) are accepted.
- Typos in the offices list are left as they are.
- **OVG** has a section in the 2026 charter (4 services).
- **LEDIPO** is not in the 2026 charter, so it is not added.
- **IDH and KDH:** the 2026 charter supersedes both Word versions.

## Summary

- **34 offices**, **241 services** (sub-services counted
  separately, written as “Parent – Service”).
- OSMP and OG-OPA have no section of their own; both are duplicates (above).
- The office drafts (Word files) had 217 services; the 2026 charter adds new
  ones (for example BGH HEENT and Social Services, KDH certificates, DMDH
  hemodialysis, PHO YAKAP clinic, PVO stunted-children support) and drops a
  few (for example OG *Burial Assistance to Veterans*, the LEDIPO services).
- **Classification** (RA 11032: Simple within 3 working days, Complex 7,
  Highly Technical 20): Simple 196, Complex 29, Highly Technical 16.
- **Internal/External** is not labelled anywhere in the charter.

| Code | Office | Services |
|---|---|---:|
| OG | Provincial Governor’s Office – Main and Administrative Division | 7 |
| OG-Records | Records Section | 2 |
| OG-IT | Information Technology / Management Information Services | 3 |
| OG-PESO | Public Employment Services Office | 1 |
| OG-BAC | Bids and Awards Committee | 3 |
| OG-CAO | Community Affairs Office | 1 |
| OG-SDO | Sports Development Office | 3 |
| OG-BTS | Benguet Technical School | 8 |
| OG-PDRRMO | Provincial Disaster Risk Reduction and Management Office | 7 |
| OG-PTCAO | Tourism | 7 |
| OG-PWO | Provincial Warden’s Office | 4 |
| OG-Provincial Library | Provincial Library | 5 |
| OVG | Provincial Vice-Governor’s Office | 4 |
| PAccO | Provincial Accounting Office | 2 |
| PAgO | Provincial Agriculturist Office | 7 |
| PAssO | Provincial Assessor’s Office | 6 |
| PBO | Provincial Budget Office | 2 |
| PEO | Provincial Engineer’s Office | 4 |
| PENRO-LGU | Provincial Environment and Natural Resources Office | 4 |
| PGSO | Provincial General Services Office | 3 |
| PHO | Provincial Health Office | 7 |
| PHRMDO | Provincial Human Resource Management and Development Office | 1 |
| PLO | Provincial Legal Office | 2 |
| PPDO | Provincial Planning and Development Office | 3 |
| PSWDO | Provincial Social Welfare and Development Office | 7 |
| PTO | Provincial Treasurer’s Office | 16 |
| PVO | Provincial Veterinarian’s Office | 19 |
| OSSP | Office of the Secretary to the Sanggunian – Records Section | 2 |
| ADH | Atok District Hospital | 6 |
| DMDH | Dennis Molintas District Hospital | 14 |
| IDH | Itogon District Hospital | 13 |
| KDH | Kapangan District Hospital | 13 |
| NBDH | Northern Benguet District Hospital | 14 |
| BeGH | Benguet General Hospital | 41 |

## Internal services (playbook Q7)

The 2026 charter doesn’t label any service Internal or External. Under ARTA,
**internal** services are those provided to the agency’s own offices and
employees. Judging by *who may avail*, these look internal; everything else
serves citizens, businesses or other governments and would be **External**:

| Office | Service | Who may avail (charter) |
|---|---|---|
| PAccO | Issue Certifications (premium and loan payments; claims on traveling expenses and other benefits) | Provincial Government officials and employees |
| PEO | Provide Transportation Services | PLGU and other government offices |
| PEO | Perform Preventive & Corrective Maintenance Services | PLGU and other government offices (provincial vehicles and equipment) |
| PTO | Payment of Approved Vouchers and Payrolls | Payees, including employees |
| PTO | Collection of Payment of Telephone Bills (personal calls), Loans, Bid and Performance Securities… | Concerned personnel / citizens |
| PTO | Collection of Remittance of Revenue Division, Field Cashiers, and other Accountable Officers | Officials and personnel of the LGU |
| PHO | Request for Repair & Preventive Maintenance of Bio-Medical Equipment | Rural health units and district hospitals |
| OG-Records | Receiving and Dispatching of Financial Documents | Government to Government |
| OG-Provincial Library | General Clearance | Government to Government (employee clearances) |
| PHRMDO | Issuance of HR Documents | Active and former officials and employees |

### Decision (2026-10-02)

A service is **Internal** only when it is used solely by the Provincial
Government’s own offices and employees; if citizens, suppliers, bidders,
other agencies or municipalities also use it, it stays **External**. Internal
and External results are reported separately, and an Internal respondent
should be a provincial employee (client type Government – Employee), so a
mixed service marked Internal would put citizens’ answers into the internal
results.

**Internal (6):** PAccO *Issue Certifications*; PEO *Perform Preventive &
Corrective Maintenance Services* (provincial vehicles and equipment); PTO
*Collection of Remittance of Revenue Division, Field Cashiers, and other
Accountable Officers*; OG-Records *Receiving and Dispatching of Financial
Documents*; OG-Provincial Library *General Clearance*; PHRMDO *Issuance of
HR Documents*.

**Stay External (4):** PEO *Provide Transportation Services* (other government
offices too); PTO *Payment of Approved Vouchers and Payrolls* (suppliers are
payees); PTO *Collection of Payment of Telephone Bills, Loans, Bid and
Performance Securities* (bidders and citizens pay); PHO *Request for Repair &
Preventive Maintenance of Bio-Medical Equipment* (rural health units belong to
the municipalities).

To confirm with the offices: whether OG-Records also receives financial
documents from outside agencies, and whether PEO maintains vehicles of other
agencies. If so, change that service back to External in the Services screen.

The seed file `src/api/database/seeders/data/services-2026.json` carries these
types, so a fresh setup gets the same result.

## The official CSM form (Annex A)

Annex A is the **Provincial Government of Benguet Client Satisfaction
Measurement Form**. It differs from the tally sheet (`CSMF blank form.xlsx`)
the playbook was based on:

| Field | Annex A (official form) | Tally sheet / playbook |
|---|---|---|
| Control No. | Blank at top | Not present |
| Client type | Citizen · Business · Government (Employee or another agency) | Government split into Employee and Other Agency |
| Date | Free text | Transaction date |
| Sex | Male / Female | Same |
| Age | Number written by the client | Age brackets (19 or lower … 65 or higher) |
| Region of residence | Free text | Pick list |
| Service Availed | Free text | Pick from the office’s services |
| CC1–CC3, SQD0–SQD8 | Same wording and options as the playbook | Same |
| Suggestions, Email | Both optional | Same |

The 5-to-1 “Very Satisfied … Very Dissatisfied” scale (`Picture.jpg`) is not on
the official form.

## Feedback and complaints mechanism (charter p. 395)

Feedback forms go in the drop box at the PACD/Capitol entrance. PHRMDO verifies
them within one working day and refers them to the concerned office; the client
is told the outcome by email or phone. Complaints go to the Anti-Red Tape Unit
(ARTU) by email (phrmdo@benguet.gov.ph / benguet.governor@gmail.com), which
reports to the Provincial Governor.

## Office by office

### OG — Provincial Governor’s Office – Main and Administrative Division

7 services. OG-OPA (Provincial Administrator) is the same as Main (confirmed 2026-10-02).

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Issuance of Governor's Endorsement / Recommendation | Simple | G2C | Residents of the Province of Benguet Students requesting scholarship … |
| 2 | Scheduling of Courtesy Call / Appointment with the Governor | Simple | G2C, G2B, G2G | Local Government Officials National Government Agencies Civil Society… |
| 3 | Endorsement of Patients for PhilHealth Sponsorship | Simple | G2C, G2B, G2G | Local Government Officials National Government Agencies Civil Society… |
| 4 | Transportation Service Request | Simple | G2C, G2B, G2G | Provincial Government Offices LGUs and Partner Agencies Individuals w… |
| 5 | Assistance To Provincial School Board (PSB) Teachers | Simple | G2C | Professional Regulation Commission (PRC) Licensed Teachers endorsed b… |
| 6 | Assistance To Medical Students (Study Now, Pay Later Program) | Simple | G2C | Medicine Students |
| 7 | Assistance to Provincial Scholars (The College Educational Scholarship Program) | Simple | G2C | Indigent students from Benguet |

### OG-Records — Records Section

2 services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Receiving and Dispatching of Communications | Simple to Highly Technical | G2C, G2G | External & Internal Clients |
| 2 | Receiving and Dispatching of Financial Documents | Simple to Highly Technical | G2G | Internal Clients |

### OG-IT — Information Technology / Management Information Services

3 services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Request for Outdoor LED Wall Postings (Paid) | Simple | G2C | Clients/Citizens |
| 2 | Request for Outdoor LED Wall Postings | Simple | G2G | Provincial departments/other agencies |
| 3 | Provide CCTV Footage Requests | Simple | G2G | Provincial departments/other agencies |

### OG-PESO — Public Employment Services Office

1 service.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Employment Facilitation | Simple | G2C, G2B, G2G | Citizen, Company, Client, Jobseekers who can avail the service |

### OG-BAC — Bids and Awards Committee

3 services. One BAC section with three services. The charter’s **List of Offices** names three committees with separate addresses: BAC on Goods (Poblacion), BAC on Health and BAC on Infrastructure (both Km. 5, Pico). Bid-document fees differ per committee (“For BAC Health …”).

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Sale and Issuance of Bidding Documents | Simple | G2B | Government to Businesses (G2B) |
| 2 | Provide updates on Verbal Request/s for follow-up/s | Simple | G2B | All Offices, External, Internal, Suppliers/ Dealers and Contractors |
| 3 | Issuance of Procurement Documents and Records | Simple | G2B | All Offices, External, Internal, Suppliers/ Dealers and Contractors |

### OG-CAO — Community Affairs Office

1 service.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Assistance/Participation/Validation and/or Monitoring of Barangay Activities of SK, NGOs, and CSOs Coordinated in the Office | Simple | G2C, G2G, G2N | Citizen, Company, Client |

### OG-SDO — Sports Development Office

3 services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Financial Assistance for Athletes Representing the Province of Benguet All Levels | Simple | G2C, G2N | Citizen, Company, Client |
| 2 | Coordination/Administration/Technical Assistance of Sports Activities in the Promotion of Philippine Sport at All Levels | Simple | G2C, G2G, G2N | Citizen, Company, Client |
| 3 | Partnership/Information Dissemination in the Promotion of Philippine Sports at All Levels | Simple | G2C, G2G, G2N | Citizen, Company, Client |

### OG-BTS — Benguet Technical School

8 services. Service 8 is *Assistance to the Conduct of Community Service*. The repeated *Request for School Credentials and Certifications* in `2026 services.docx` was a copy error.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Enrollment for Scholarship Program | Simple | G2C | a) Qualified TESDA Scholars (under TWSP, STEP, and TTSP) b) Qualified… |
| 2 | Enrollment for Regular Program | Simple | G2C | Interested Individuals |
| 3 | Enrollment for OWWA's Skills for Employment Scholarship Program | Simple | G2C | Qualified OWWA Members or their Authorized dependents |
| 4 | Assessment for Scholarship Beneficiaries | Simple | G2C | a. TESDA Scholars with Relevant NC I and NC II training certificate b… |
| 5 | Assessment of Regular/Walk-in applicants | Simple | G2C | with a. Applicant with NC I and NC II training certificate b. Industr… |
| 6 | Request for School Credentials and Certifications | Simple | G2C | Completers or Graduates of BTS |
| 7 | Assistance to the Conduct of Community-Based Training (CBT) | Simple | G2C | JHS and SHS Leaners enrolled in DepEd Public Schools in Benguet Civic… |
| 8 | Assistance to the Conduct of Community Service | Simple | G2C | Government Employees (LGU/DepEd) JHS and SHS Schools in Benguet |

### OG-PDRRMO — Provincial Disaster Risk Reduction and Management Office

7 services. Seven services under three headings, written as “Heading – Service”.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Disaster Preparedness – Provision of Capability Building | Simple | G2C, G2G | Eligible stakeholders |
| 2 | Disaster Response – Transport Services (Ambulance, Cadaver, Other Logistics) | Simple | G2C, G2G, G2N | Eligible Citizen/Client, Government |
| 3 | Disaster Response – Heavy Equipment Support Services | Complex | G2C, G2G | Communities, leaders, groups, or individuals concern |
| 4 | Disaster Response – Damage Assessment Inspection | Simple | G2G, G2N | Citizens, Government Agencies |
| 5 | Disaster Response – Emergency Response (Normal Days/During Disaster) | Simple | G2C, G2G | emergencies/disaster as well as unexpected events such as vehicular a… |
| 6 | Rehabilitation and Recovery – Provision of Emergency Goods (Food and Non-food Items), Relief and Recovery Assistance | Simple | G2C, G2G | Eligible clients affected by natural and/or human induced disasters w… |
| 7 | Rehabilitation and Recovery – Request for Funding from the LDRRMF (Structural and Non-structural PPAs) | Simple | G2G, G2N | B/MLGUs, Government Offices |

### OG-PTCAO — Tourism

7 services. Seven services. The two LEDIPO services in the older office draft (`PGO-TOURISM.docx`) are **not** in the 2026 charter.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Assistance to Researchers and Students | Simple | G2C | Citizen/Client |
| 2 | Assistance to Excursionists, Tourists and Media | Simple | G2C, G2G | Citizen/Client |
| 3 | Receiving and Dispatching of Official Communications | Simple | G2C, G2G | Citizen/Client |
| 4 | Acceptance of Project Proposals to the National Commission for Culture and the Arts (NCCA) | Simple | G2C, G2G | Citizen/Client |
| 5 | Rental of Cultural Instruments and Benguet Traditional Attire | Simple | G2C | Citizen/Client |
| 6 | Assistance to Researchers and Students (Museum) | Simple | G2C | Citizen/Client |
| 7 | Assistance to Museum Clients/Guests | Simple | G2C | Citizen/Client |

### OG-PWO — Provincial Warden’s Office

4 services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Acceptance of Person Deprived of Liberty (PDL) committed by PNP, NBI, and other law enforcement agencies | Simple | G2C, G2G | Visitors, Lawyers, Counsel |
| 2 | Acceptance of Person Deprived of Liberty visitors/lawyers | Simple | G2C, G2G | Visitors, Lawyers, Counsel |
| 3 | Received Court Orders from different courts | Simple | G2G | PDL |
| 4 | Requests for Certificate of Detention/Discharge/GCTA/TASTM | Simple | G2C, G2G | PDL, lawyers, other agencies |

### OG-Provincial Library — Provincial Library

5 services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Registration of Library Clients | Simple | G2C | Library Clients / Readers |
| 2 | Assistance to Library Clients on their researches | Simple | G2C | Library Clients / Readers FEES TO |
| 3 | Borrowing of library reading materials for overnight-use | Simple | G2C | Library Clients / Readers |
| 4 | Assistance to Online services through the Technology for Economic Development - Digital Transformation Center (Tech4Ed) | Simple | G2C | Library Clients / Readers |
| 5 | General Clearance | Simple | G2G | Library Clients / Readers |

### OVG — Provincial Vice-Governor’s Office

4 services. Four services; this office has no separate Word draft, only this charter.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Livelihood Assistance Proposals to SP MSME Accredited Associations | Simple | G2C | SP Accredited MSME Associations in Benguet |
| 2 | Request for a Vehicle and Driving Services | Simple | G2C, G2B, G2G | PLGU Offices / Walk-In Clients |
| 3 | Request for an Appointment with the Provincial Vice Governor | Simple | G2C, G2B, G2G | Requesting Party / Authorized Representative |
| 4 | Request For the Enforcement of Laws and Ordinances | Simple | G2C, G2B, G2G | Requesting Party |

### PAccO — Provincial Accounting Office

2 services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Issue Certifications (a. Premium and loan payments; b. claims on traveling expenses and other benefits) | Simple | G2C, G2G | Provincial Government of Benguet officials and employees or their aut… |
| 2 | Issue Certified Copies of Disbursement Vouchers and/or its' supporting documents | Simple | G2C | Citizens |

### PAgO — Provincial Agriculturist Office

7 services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Issue Agricultural Production/Data | Simple | G2C, G2B, G2G | Government entities; private companies; Researchers; Farmers/Farmers … |
| 2 | Acquisition of Planting Materials | Simple | G2C, G2B, G2G | Government and private entities; general public |
| 3 | Technical Guidance/Assistance (Inquiries, Referral, Submission of Documents) | Simple; Highly Technical | G2C, G2B, G2G | Government entities; private companies; Researchers; Farmers/Farmers … |
| 4 | Renting out of facilities | Simple | G2C, G2B, G2G | Government and private entities, General public |
| 5 | Livelihood Loan Assistance | Simple to Complex | G2C, G2B | Organizations (Associations/Cooperatives) |
| 6 | Cold Chain Services | Simple | G2C, G2B, G2G | Government and private entities, General public |
| 7 | Validation / Monitoring & inspection services for Project/Program proposals related to INS, FMR and Mechanization Matters | Simple; Highly Technical | G2C, G2B, G2G | Farmers/Fisherfolks Cooperatives Associations (FCA's), Farmers Associ… |

### PAssO — Provincial Assessor’s Office

6 services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Processing and Approval of Tax Declaration of Real Property (TDRP) | Complex | G2C, G2B, G2G | General Public |
| 2 | Certified/Plain copy of Sketch Plan, Section map and Tax Mapping Control Roll (TMCR); Technical Assistance. | Simple | G2C, G2B, G2G | General Public |
| 3 | Annotation/Cancellation of Mortgage and other Legal Orders | Simple | G2C, G2B, G2G | General Public |
| 4 | Issuance of Certified Copy of TDRP and Supporting Documents (with or without TDRP number) | Simple | G2C, G2B, G2G | General Public |
| 5 | Issuance of Certificate of Property Holdings/Non-Property, Assessment, Non-encumbrance, etc. | Simple | G2C, G2B, G2G | General Public |
| 6 | History of Real Property | Simple | G2C, G2B, G2G | General Public |

### PBO — Provincial Budget Office

2 services. Two separate services (walk-in assistance, and assistance to municipal/barangay officials), not a duplicate.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Assist Walk-in Municipal/Barangay Officials Regarding Budgetary Matters | Simple & Complex | G2C, G2G | Private citizens and Government employees and officials |
| 2 | Assistance to Municipal/Barangay Officials Regarding Budgetary Matters | Simple & Complex | G2C, G2G | Private citizens and Government employees and officials |

### PEO — Provincial Engineer’s Office

4 services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Issue Road Right-of- Way Certification | Simple/Complex | G2C | Private Citizen |
| 2 | Perform Materials Testing Procedures | Complex | G2B | Project Contractors |
| 3 | Provide Transportation Services | Simple | G2G | PLGU And Other Government Offices |
| 4 | Perform Preventive & Corrective Maintenance Services | Simple/Complex/Highly Technical | G2G | PLGU And Other Government Offices |

### PENRO-LGU — Provincial Environment and Natural Resources Office

4 services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Issue Quarry Permit | Complex and Highly Technical | G2C, G2G | Any qualified individuals, Juridical Entity |
| 2 | Issue Small Scale Mining Contract/Permit | Complex and Highly Technical | G2C, G2G | Any qualified individuals, Juridical Entity |
| 3 | Issue Ore Transport Permit | Complex and Highly Technical | G2C, G2G | Any qualified individuals |
| 4 | Issue Mineral Processor's Permit | Complex and Highly Technical | G2C, G2G | Any qualified individuals, Juridical entity |

### PGSO — Provincial General Services Office

3 services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Serve Purchase Orders | Simple | G2C | Clients / Citizens |
| 2 | Inspection and Acceptance of Deliveries | Simple - Complex | G2C | Clients / Citizens |
| 3 | Renting Out of Facilities | Simple | G2G | Provincial departments/other agencies |

### PHO — Provincial Health Office

7 services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Microbiological Water Analysis | Complex | G2C, G2G | Municipalities and General Public |
| 2 | NTP Laboratory - Xpert MTB RIF Assay | Simple | G2C, G2G | Municipalities, Health Facilities and General Public |
| 3 | Provision of Technical Assistance | Simple | G2C | Municipalities, Partner agencies and General Public |
| 4 | Issuance of Vaccines, Medicines and Medical Supplies | Simple | G2C | Rural Health Units, hospitals, General Public, Municipal Health servi… |
| 5 | Issuance of Environmental Health and Sanitation Certificate | Complex | G2G | Individuals, agencies or companies |
| 6 | Request for Repair & Preventive Maintenance of Bio-Medical Equipment | Highly Technical | G2G | Rural Health Units and District Hospitals |
| 7 | Consultation Services at Benguet Provincial Health Office Yaman ng Kalusugan Program (YAKAP) Clinic | Simple | G2C | All PhilHealth Members |

### PHRMDO — Provincial Human Resource Management and Development Office

1 service. Who may avail: active and former officials and employees.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Issuance of HR Documents (Service Records, Certificate of Employment, Earned Leaves) and Other HR Records | Simple | — | Active and Former Officials and Employees |

### PLO — Provincial Legal Office

2 services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Rendition of Legal Advice/Opinion/Counseling | Simple | G2C, G2B, G2G | Any client |
| 2 | Issuance of Certified True Copy of Official Documents | Simple | G2C, G2G | Any client |

### PPDO — Provincial Planning and Development Office

3 services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Verification/Request Project/s Listings and Prioritized Projects and Other Documents | Simple | G2C, G2B, G2G | Citizens/Clients, Other LGUs/Offices, Line Agencies |
| 2 | Validation/ Inspection/ Monitoring of Social, Economic, and Infrastructure Projects/ Issuance of Validation/Inspection, and Monitoring Reports | Simple, Complex & Highly Technical | G2C, G2B, G2G | Citizens/Client, Offices, Line Agencies, MLGUs/BLGUs |
| 3 | Provision of Development Plans, Investment programs, maps, demographic data, Physical and Socio-Economic Data, and reports such as Semestral and Annual Projects Status, and Others. | Simple | G2C, G2B, G2G | Offices, Agencies, Students, Researchers, Funding Agencies, etc. |

### PSWDO — Provincial Social Welfare and Development Office

7 services. *Food for work, Food Assistance* and *Food Assistance* are two separate services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Financial Assistance to Individuals in Crisis Situation (AICS) | Complex & Highly Technical | G2C | All clients in crisis situations due to illness, death, disasters, ot… |
| 2 | Children in Conflict with the Law for Admission at Bahay Pag-asa | Complex and Highly Technical | G2C | Children - in - Conflict with the Law whose cases are being heard in … |
| 3 | Children in Conflict with the Law (with court decision) for transfer to Rehabilitation Center | Complex | G2C | Bahay Pag-asa residents (CICL) with court decision for transfer to th… |
| 4 | Food for work, Food Assistance | Complex | G2C | All clients & families in crisis situation, Organizations, Barangay &… |
| 5 | Food Assistance | Complex | G2C | All clients & families in crisis situation, Organizations, Barangay &… |
| 6 | Provision of Assistive Devices | Complex | G2C | All PWDs in need of assistive devices |
| 7 | Livelihood Loan Assistance Program (Maximum-1,000,000.00; Minimum-100,000.00) | Complex and Highly Technical | G2C, G2B | Non-Government Organizations/ Peoples Organizations |

### PTO — Provincial Treasurer’s Office

16 services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Collection of tax on Transfer of Real Property Ownership | Simple | G2C | All Taxpayers |
| 2 | Collection of Tax on Sand, Gravel, and Other Quarry Resources to Contractors | Simple | G2C | Project Contractors |
| 3 | Collection of Sand and Gravel Tax and Issuance of Delivery Receipts to the Permittee | Simple | G2C | Sand and Gravel Permittees |
| 4 | Collection of Real Property Tax | Simple | G2C | Real Property Taxpayers/Owners |
| 5 | Collection of Professional Tax | Simple | G2C | Professional who passed the examinations conducted by the PRC/IBP/ ot… |
| 6 | Collection of Franchise Tax & Tax on Printing & Publication | Simple | G2C | Taxpayers / Business Owners |
| 7 | Collection of Annual Fixed Tax on Delivery Trucks/ Vans | Simple | G2C | Business Owners with Delivery Vehicle/s |
| 8 | Collection of Fees and Issuance of Provincial Permit/ Clearance | Simple | G2C | Client/Taxpayer |
| 9 | Collection of certification Fees, Verification Fees, Rentals, Occupation Fees, Tuition Fees, Bid Documents, Other Fees and Charges | Simple | G2C | Clients/Taxpayers |
| 10 | Payment of Approved Vouchers and Payrolls | Simple | G2C | Claimants/concerned clients |
| 11 | Release of Approved Checks in Payment of Obligations | Simple | G2C | Concerned Citizen |
| 12 | Collection of Payment of Telephone Bills (personal calls), Loans, Bid and Performance Securities, and Other Miscellaneous payments | Simple | G2C | Concerned personnel/concerned citizen |
| 13 | Receipts of Other Agency Transfer Fund | Simple | G2G | LGUs/Authorized Representatives |
| 14 | Collection of Remittance of Revenue Division, Field Cashiers, and other Accountable Officers | Simple | G2G | Officials and other Personnel of the LGU / concerned citizen |
| 15 | Collection of Remittance of Municipal Treasurers on Provincial Shares | Simple | G2G | LGUs |
| 16 | Issuance of Accountable Forms | Simple | G2G | Bonded Accountable Officers |

### PVO — Provincial Veterinarian’s Office

19 services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Issuance of Veterinary Health Certificate (VHC) and Veterinary Shipping Permit (VSP) | Simple to Complex | G2C, G2B | Livestock Raisers, Pet Owners, Livestock Shippers, Businessmen |
| 2 | Vaccination of Animals | Simple | G2C | Livestock Raisers, Pet Owners |
| 3 | Deworming of Animals | Simple | G2C | Livestock Raisers, Pet Owners |
| 4 | Supplementation of Animals with Vitamins and Minerals | Simple | G2C | Livestock Raisers, Pet Owners |
| 5 | Treatment of Animals | Simple to Complex, Highly Technical | G2C | Livestock Raisers, Pet Owners |
| 6 | Consultation | Simple | G2C | Livestock Raisers, Pet Owners |
| 7 | Spay and Neuter of Pets | Highly Technical | G2C | Pet Owners |
| 8 | Castration of Livestock | Highly Technical | G2C | Livestock Raisers |
| 9 | Animal Disease Surveillance/ Investigation | Highly Technical | G2C | Livestock Raisers, Pet Owners |
| 10 | Biosecurity Assessment of Animal Facilities | Simple | G2C | Livestock Raisers, Pet Owners |
| 11 | Animal Quarantine Inspection | Simple to Complex | G2C, G2B | Livestock Shippers/Owners/Raisers, Businessmen |
| 12 | Artificial Insemination (AI) of Swine | Highly Technical | G2C | Livestock Raisers |
| 13 | Artificial Insemination (AI) of Ruminant Animals (Sheep, Goat, Cattle and Carabao) | Highly Technical | G2C | Livestock Raisers |
| 14 | Natural Breeding of Cattle | Simple | G2C | Livestock Raisers |
| 15 | Animal Dispersal and Buck loan | Complex | G2C | Livestock Raisers |
| 16 | Livelihood Loan Assistance (Minimum-PHP100,000.00; Maximum- PHP1,000,000.00) | Complex | G2C, G2B | Farmers' Cooperative/Associations (FCAs) or Organization |
| 17 | Fingerlings (Tilapia) Dispersal | Simple to Complex | G2C | Fisherfolks, Farmers |
| 18 | Calamity Assistance | Complex | G2C | Farmers, Fisherfolks, Beekeeper |
| 19 | Support to Families with Stunted Children | Complex | G2C | Families with stunted children |

### OSSP — Office of the Secretary to the Sanggunian – Records Section

2 services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Provision of Resolutions, Ordinances, and other Legislative Records | Simple | G2C | Client/citizen |
| 2 | Provision of Certifications | Simple | G2C | Client/citizen |

### ADH — Atok District Hospital

6 services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | OPD Consultation | Simple | G2C | Individuals/concern people/patients |
| 2 | Pharmacy Services | Simple | G2C | Individuals/concern people/patients |
| 3 | Billing | Simple | G2C | Individuals/concern people/patients |
| 4 | Ambulance Services | Simple | G2C | Individuals/concern people/patients |
| 5 | Referral of Patients to other Facilities | Simple | G2C | Individuals/concern people/patients |
| 6 | Availing of X-ray Services | Simple | G2C | Individuals/concern people/patients |

### DMDH — Dennis Molintas District Hospital

14 services.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | OPD Consultation | Complex | G2C | Patients |
| 2 | Dispensing of Medicines | Simple | G2C | Patients |
| 3 | Paying old Accounts | Complex | G2C | Patients |
| 4 | Request for an Ambulance | Complex | G2G | Patients For Referral |
| 5 | Referring a Patient to Higher Facilities | Highly Technical | G2G | Patients |
| 6 | Emergency Room Consultation | Complex | G2C | Patients |
| 7 | Patients Who Will Undergo Operation | Highly Technical | G2C | Patients |
| 8 | Admission of Patient | Complex | G2C | Patients |
| 9 | Discharge of Patient | Complex | G2C | Patients |
| 10 | Laboratory Services | Simple | G2C | All Citizens/Public/All Patients Attended by NBDH |
| 11 | Provision of X-Ray and Ultrasound | Simple | G2C | All Citizens/Public/All Patients Attended by NBDH |
| 12 | Provision of Dental Services | Simple | G2C | All Citizens/Public |
| 13 | Provision of Medical Assistance for Indigent Financially Incapacitated Patient (MAIFIP) | Simple | G2C | All Citizens/Public/ Financially Incapacitated Patient |
| 14 | Provision of Hemodialysis | Complex | G2C | All Citizens/Public/ Financially Incapacitated Patient |

### IDH — Itogon District Hospital

13 services. Service 12, *X-Ray Procedure*, has two parts: out-patient and in-patient clients.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | OPD Consultation | Simple | G2C | Citizen or client |
| 2 | Buying Medicines | Simple | G2C | Citizen or Client |
| 3 | Referring Patient to Higher Facilities | Simple | G2C | Citizen or client |
| 4 | Emergency Room Consultation | Simple | G2C | Citizen or client |
| 5 | Patients who will Undergo Operation (Minor, Local Anesthesia) | Simple | G2C | Citizen or client |
| 6 | Admission of Patient | Simple | G2C | Citizen or client |
| 7 | Discharge of Patient | Simple | G2C | Citizen or client |
| 8 | Hematology | Simple | G2C | Citizen or client |
| 9 | Clinical Microscopy & Microbiology | Simple | G2C | Citizen or client |
| 10 | Clinical Blood Chemistry | Simple | G2C | Citizen of client |
| 11 | Immunology/Serology | Simple | G2C | Citizen or client |
| 12 | X-Ray Procedure – Out Patient clients | Simple | G2C | Citizen or Client CHECKILIST OF REQUIREMENTS WHERE TO SECURE X-ray re… |
| 13 | X-Ray Procedure – In Patient Clients | Simple | G2C | In Patient CHECKILIST OF REQUIREMENTS WHERE TO SECURE X-ray request f… |

### KDH — Kapangan District Hospital

13 services. Service 10, *Paying of OPD/ER Patients*, has two parts: not covered by insurance, and covered by MAIFIPP.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | OPD Consultation | Simple | G2C | Patient / Walk in Clients |
| 2 | Emergency Room Consultation | Simple | G2C | Patients requiring emergency care |
| 3 | Animal Bite Treatment Service | Simple | G2C | Patients requiring animal bite treatment |
| 4 | Medical Certificate | Simple | G2C | Patients requiring medical certification |
| 5 | Certificate of Live Birth | Complex | G2C | Mother or Father of newborn / authorized representative |
| 6 | Certificate of Death | Simple | G2C | Authorized representative/Family Member |
| 7 | Ancillary Services - Laboratory | Highly Technical | G2C | Patients with laboratory requests |
| 8 | Ancillary Services - Radiology | Highly Technical | G2C | Patients with imaging requests |
| 9 | Ancillary Services - Pharmacy | Simple | G2C | Patients with prescriptions |
| 10 | Paying of OPD/ER Patients – Not covered by Insurance | Simple | G2C | Patients not covered by insurance |
| 11 | Paying of OPD/ER Patients – Covered by MAIFIPP (Medical Assistance to Indigent and Financially Incapacitated Patients Program) | Simple | G2C | Patients covered by MAIFIPP |
| 12 | Admitted and Discharged Patients | Highly Technical | G2C | Admitted and discharged patients |
| 13 | Referral of Patients to Higher Facilities | Complex | G2C | Patients who need further evaluation and management |

### NBDH — Northern Benguet District Hospital

14 services. Service 7 has four parts: drugs and medicines, billing of discharges, diagnostics, X-ray and ultrasound.

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Provision of Out-Patient Consultation | Simple | G2C | All Citizens/Public |
| 2 | Provision of Emergency Management to Patients at the Emergency Room | Simple | G2C | All Citizens/Public |
| 3 | Provision of Services for Admitted Patients | Simple | G2C | All Citizens/Public |
| 4 | Provision of Services to Discharged Patients | Simple | G2C | All Patients Admitted by NBDH |
| 5 | Provision of Medical Certificate, Medico-Legal Certificate, Medical Records and Consent to Release Patient's Medical Information | Simple | G2C | All Patients Attended by NBDH |
| 6 | Provision of PhilHealth Claims/Processing | Simple | G2C | All PHIC Member-Patients Attended by NBDH |
| 7 | Provision of Drugs and Medicines to Patients and Billing of Discharges – Provision of Drugs and Medicines | Simple | G2C | All Citizens/Public/All Patients Attended by NBDH |
| 8 | Provision of Drugs and Medicines to Patients and Billing of Discharges – Billing of Discharges | Simple | G2C | All Citizens/Public/All Patients Attended by NBDH |
| 9 | Provision of Drugs and Medicines to Patients and Billing of Discharges – Provision of Diagnostic | Simple | G2C | Government to Citizens All Citizens/Public/All Patients Attended by N… |
| 10 | Provision of Drugs and Medicines to Patients and Billing of Discharges – Provision of X-Ray and Ultrasound | Simple | G2C | All Citizens/Public/All Patients Attended by NBDH |
| 11 | Provision of Dental Services | Simple | G2C | All Citizens/Public |
| 12 | Provision of Medical Assistance for Indigent Financially Incapacitated Patient (MAIFIP) | Simple | G2C | All Citizens/Public/ Financially Incapacitated Patient |
| 13 | Provision of Referral of Patients to Higher Facilities | Highly Technical | G2C | All Citizens/Public/ Financially Incapacitated Patient |
| 14 | Provision of OR/DR Complex | Highly Technical | G2C | All Citizens/Public/ Financially Incapacitated Patient |

### BeGH — Benguet General Hospital

41 services. Five services have sub-services: Hemodialysis Unit (3), Surgery (6), Central Treatment Room (3), Radiology Department (11, split by paying vs Malasakit/Yakap patients and OPD vs ER/in-patients), Social Services Office (3).

| # | Service | Classification | Transaction | Who may avail |
|---:|---|---|---|---|
| 1 | Out- Patient Department (OPD) Consultation - Internal Medicine | Simple | G2C | Public |
| 2 | OPD Consultation – Hemodialysis Unit – OPD Consultation of Chronic Kidney Disease Stage 5 Patients | Highly Technical | G2C | Public |
| 3 | OPD Consultation – Hemodialysis Unit – Hemodialysis Initiation | Highly Technical | G2C | Public |
| 4 | OPD Consultation – Hemodialysis Unit – OPD Hemodialysis | Highly Technical | G2C | Public |
| 5 | Out- Patient Department (OPD) Consultation - OB-Gyne | Simple | G2C | Public |
| 6 | Out-Patient Department (OPD) Consultation - Pedia | Simple | G2C | Public |
| 7 | OPD Consultation – Surgery – Special Services – Club Foot Management | Simple | G2C | Public |
| 8 | OPD Consultation – Surgery – General Surgery | Simple | G2C | Public |
| 9 | OPD Consultation – Surgery – Uro Surgery | Simple | G2C | Public |
| 10 | OPD Consultation – Surgery – Orthopedics | Simple | G2C | Public |
| 11 | OPD Consultation – Surgery – Ophthalmology | Simple | G2C | Public |
| 12 | OPD Consultation – Surgery – HEENT | Simple | G2C | Public |
| 13 | OPD – Animal Bite Treatment Center (ABTC) | Simple | G2C | Public |
| 14 | Management of NTP (TB) Program | Simple | G2C | Public |
| 15 | Central Treatment Room – Admission Of Patient | Simple | G2C | Public |
| 16 | Central Treatment Room – Electrocardiogram (ECG) Procedure | Simple | G2C | Public |
| 17 | Central Treatment Room – Tetanus Toxoid Injection | Simple | G2C | Public |
| 18 | Radiology Department – A.1 X-Ray Procedures: OPD (Paying Patients) | Simple | G2C | Public |
| 19 | Radiology Department – A.2 X-Ray Procedures: OPD (Patients Availing Malasakit/Yakap Services) | Simple | G2C | Public |
| 20 | Radiology Department – A.3 X-Ray Procedures: ER and In-Patients | Simple | G2C | Public |
| 21 | Radiology Department – B.1 Ultrasound Procedures: OPD (Paying Patients) | Simple | G2C | Public |
| 22 | Radiology Department – B.2 Ultrasound Procedures: OPD (Patients Availing Malasakit/Yakap Services) | Simple | G2C | Public |
| 23 | Radiology Department – B.3 Ultrasound Procedures: Emergency Room and In-Patients | Simple | G2C | Public |
| 24 | Radiology Department – C.1 Mammogram – OPD (Paying Patients) | Simple | G2C | Public |
| 25 | Radiology Department – C.2 Mammogram – OPD (Patients Availing Malasakit/Yakap Services) | Simple | G2C | Public |
| 26 | Radiology Department – D.1 CT-Scan – OPD (Paying Patients) | Simple | G2C | Public |
| 27 | Radiology Department – D.2 CT-Scan – OPD (Patients Availing Malasakit Services) | Simple | G2C | Public |
| 28 | Radiology Department – D.3 CT-Scan – Emergency Room (E.R.) and In-Patients | Simple | G2C | Public |
| 29 | Discharge Of Patient | Simple-Complex | G2C | Public |
| 30 | Admission of Patient | Simple | G2C | Public |
| 31 | Pharmacy Section | Simple | G2C | Public |
| 32 | Department Of Pathology And Laboratory Medicine | Simple-Complex | G2C, G2G | Public; All patient needing laboratory examinations |
| 33 | Dental Department | Simple | G2C | Public |
| 34 | Cash Section | Simple | G2C, G2B | All |
| 35 | Dispensing Of Supply for Outpatient Department | Simple | G2C | — |
| 36 | Mental Health Unit - Psychology | Simple | G2C | Public |
| 37 | Registration of Certificate of Death | Simple | G2C | Public |
| 38 | Registration of Certificate of Live Birth | Simple | G2C | Public |
| 39 | Social Services Office – Classification of Patient | Simple | G2C | Public |
| 40 | Social Services Office – Social Work Intervention | Simple | G2C | Public |
| 41 | Social Services Office – MALASAKIT Center Assistance | Simple | G2C | Public |
