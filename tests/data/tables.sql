IF OBJECT_ID('[dbo].[testchemaview]', 'V') IS NOT NULL DROP VIEW [dbo].[testchemaview];
IF OBJECT_ID('[dbo].[testschema2]', 'U') IS NOT NULL DROP TABLE [dbo].[testschema2];
IF OBJECT_ID('[dbo].[testschema1]', 'U') IS NOT NULL DROP TABLE [dbo].[testschema1];
--
CREATE TABLE [dbo].[testschema1] (
  [foreign_key1] [int] IDENTITY NOT NULL,
  [foreign_key2] [int] NOT NULL,
  [varchar_col] [varchar](100) NULL DEFAULT NULL,
  [varchar_col2] [varchar](100) NULL DEFAULT 'text',
  [integer_col] [int] NULL DEFAULT 0,
  [decimal_col] [decimal](5,2) NOT NULL DEFAULT 1.2,
  CONSTRAINT [PK_testschema1] PRIMARY KEY CLUSTERED (
    [foreign_key1], [foreign_key2] ASC
  )
);
--
CREATE TABLE [dbo].[testschema2] (
  [local_key1] [int] IDENTITY NOT NULL,
  [local_key2] [int] NOT NULL,
  [int_unique1] [int] NULL DEFAULT 42,
  [int_unique2] [int] NOT NULL,
  [int_unique3] [int] NOT NULL UNIQUE ,
  CONSTRAINT [FK_testschema1] FOREIGN KEY ([local_key1], [local_key2])
  REFERENCES [dbo].[testschema1] (foreign_key1, foreign_key2)
);
CREATE UNIQUE INDEX UQ_varchar1and2 ON [dbo].[testschema2] ([int_unique1], [int_unique2]);
--
CREATE VIEW [dbo].[testchemaview] AS SELECT * FROM [dbo].[testschema1] WHERE integer_col > 10;