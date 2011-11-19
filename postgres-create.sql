-- Role: littr

-- DROP ROLE littr;
-- pass: asd
CREATE ROLE littr LOGIN
  NOSUPERUSER INHERIT NOCREATEDB NOCREATEROLE NOREPLICATION;

-- Database: littr

-- DROP DATABASE littr;

CREATE DATABASE littr
  WITH OWNER = littr
       ENCODING = 'UTF8'
       TABLESPACE = pg_default
       LC_COLLATE = 'C'
       LC_CTYPE = 'en_US.UTF-8'
       CONNECTION LIMIT = -1;
       GRANT ALL ON DATABASE littr TO littr;

-- Table: data

-- DROP TABLE data;

CREATE TABLE data
(
      uri character varying NOT NULL,
      content character varying,
      created timestamp without time zone DEFAULT now(),
      secret character varying,
      modified timestamp without time zone DEFAULT now(),
      CONSTRAINT pk_uri PRIMARY KEY (uri )
)
WITH (
      OIDS=FALSE
);

ALTER TABLE data
  OWNER TO littr;

-- Function: update_created_column()

-- DROP FUNCTION update_created_column();

CREATE OR REPLACE FUNCTION update_created_column()
  RETURNS trigger AS
$BODY$
  BEGIN
        NEW.created = NOW();
            RETURN NEW;
              END;
            $BODY$
              LANGUAGE plpgsql VOLATILE
              COST 100;
            ALTER FUNCTION update_created_column()
              OWNER TO littr;

-- Function: update_modified_column()

-- DROP FUNCTION update_modified_column();

CREATE OR REPLACE FUNCTION update_modified_column()
  RETURNS trigger AS
$BODY$
  BEGIN
        NEW.modified = NOW();
            RETURN NEW;
              END;
            $BODY$
              LANGUAGE plpgsql VOLATILE
              COST 100;
            ALTER FUNCTION update_modified_column()
              OWNER TO littr;

-- Trigger: update_created_instime on data

-- DROP TRIGGER update_created_instime ON data;

CREATE TRIGGER update_created_instime
  AFTER INSERT
  ON data
  FOR EACH ROW
  EXECUTE PROCEDURE update_created_column();

-- Trigger: update_modified_modtime on data

-- DROP TRIGGER update_modified_modtime ON data;

CREATE TRIGGER update_modified_modtime
  BEFORE UPDATE
  ON data
  FOR EACH ROW
  EXECUTE PROCEDURE update_modified_column();

