CREATE TABLE invoices (
    invoice_id VARCHAR (50) NOT NULL,
    reference_person_id VARCHAR (50) NOT NULL,
    external_invoice_id VARCHAR (50) NOT NULL,
    text_1 VARCHAR (1000),
    text_2 VARCHAR (1000),
    PRIMARY KEY (invoice_id),
    UNIQUE (external_invoice_id),
    FOREIGN KEY (reference_person_id) REFERENCES people (person_id) ON DELETE CASCADE
);

CREATE TABLE invoices_log (
    row_id INTEGER PRIMARY KEY,
    invoice_id VARCHAR (50) NOT NULL,
    created_at INTEGER NOT NULL,
    action VARCHAR (20) NOT NULL,
    action_data VARCHAR (1000),
    FOREIGN KEY (invoice_id) REFERENCES invoices (invoice_id) ON DELETE CASCADE,
    UNIQUE (invoice_id, row_id)
);

CREATE TABLE invoices_lines (
    invoice_id VARCHAR (50) NOT NULL,
    line_number INTEGER NOT NULL,
    text VARCHAR (100) NOT NULL,
    unit_count INTEGER,
    unit_price INTEGER,
    FOREIGN KEY (invoice_id) REFERENCES invoices (invoice_id) ON DELETE CASCADE,
    PRIMARY KEY (invoice_id, line_number)
);
