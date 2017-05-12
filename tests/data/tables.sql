IF OBJECT_ID('[dbo].[testchemaview]', 'V') IS NOT NULL
  DROP VIEW [dbo].[testchemaview];
IF OBJECT_ID('[dbo].[testschema2]', 'U') IS NOT NULL
  DROP TABLE [dbo].[testschema2];
IF OBJECT_ID('[dbo].[testschema1]', 'U') IS NOT NULL
  DROP TABLE [dbo].[testschema1];
--
CREATE TABLE [dbo].[testschema1] (
  [foreign_key1] [INT] IDENTITY  NOT NULL,
  [foreign_key2] [INT]           NOT NULL,
  [varchar_col]  [VARCHAR](100)  NULL     DEFAULT NULL,
  [varchar_col2] [VARCHAR](100)  NULL     DEFAULT 'text',
  [integer_col]  [BIGINT]        NULL     DEFAULT 0,
  [decimal_col]  [DECIMAL](5, 2) NOT NULL DEFAULT 1.2,
  [float_col]    [FLOAT]         NULL,
  [tiny_col]     [TINYINT]       NOT NULL,
  [bit_col]      [BIT]           NOT NULL,
  [bin_col]      [VARBINARY]     NOT NULL DEFAULT 0xe240,
  [geo_col]      [GEOMETRY]      NOT NULL DEFAULT geometry::STGeomFromText('LINESTRING (100 100, 20 180, 180 180)', 0),
  CONSTRAINT [PK_testschema1] PRIMARY KEY CLUSTERED (
    [foreign_key1], [foreign_key2] ASC
  )
);
--
CREATE TABLE [dbo].[testschema2] (
  [local_key1]  [INT] IDENTITY NOT NULL,
  [local_key2]  [INT]          NOT NULL,
  [int_unique1] [INT]          NULL DEFAULT 42,
  [int_unique2] [INT]          NOT NULL,
  [int_unique3] [INT]          NOT NULL UNIQUE,
  CONSTRAINT [FK_testschema1] FOREIGN KEY ([local_key1], [local_key2])
  REFERENCES [dbo].[testschema1] (foreign_key1, foreign_key2)
);
CREATE UNIQUE INDEX UQ_varchar1and2
  ON [dbo].[testschema2] ([int_unique1], [int_unique2]);
--
CREATE VIEW [dbo].[testchemaview] AS
  SELECT *
  FROM [dbo].[testschema1]
  WHERE integer_col > 10;