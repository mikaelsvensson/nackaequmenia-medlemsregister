CREATE TABLE people (
    person_id VARCHAR (50) NOT NULL,
    pno VARCHAR (10),
    PRIMARY KEY (person_id),
    UNIQUE (pno)
);

CREATE TABLE people_properties (
    row_id INTEGER PRIMARY KEY,
    person_id VARCHAR (50) NOT NULL,
    created_at INTEGER NOT NULL,
    first_name VARCHAR (100),
    sur_name VARCHAR (100),
    other_names VARCHAR (100),
    phone VARCHAR (20),
    email VARCHAR (100),
    source VARCHAR (100),
    guardian_1_person_id VARCHAR (50),
    guardian_2_person_id VARCHAR (50),
    UNIQUE (person_id, row_id),
    FOREIGN KEY (person_id) REFERENCES people (person_id) ON DELETE CASCADE,
    FOREIGN KEY (guardian_1_person_id) REFERENCES people (person_id) ON DELETE CASCADE,
    FOREIGN KEY (guardian_2_person_id) REFERENCES people (person_id) ON DELETE CASCADE
);
