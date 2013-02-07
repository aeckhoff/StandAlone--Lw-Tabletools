StandAlone--Lw-Tabletools
=========================
-------------------------
lw_tabletool_stand_alone is a tool which works independently from Contentory.

This tool's features are:

- creation of databasetables from imported xml database structure and data.
- creation of xml code from exsting database tables and data for export.
- update tables based on given xml structure.


-------

##### allowed ATTRIBUTE_TYPEs :

###### Mysql
- number (int, bigint, tinyint)
- text  (varchar, text)  
- clob  (mediumtext, longtext)

###### SIZE INFO:
- number size <= 11 sets "int" > 11 sets "bigint" 
- text size <= 255 sets "varchar" > 255 & <= 4000 sets "text" > 4000 sets "longtext"

###### Oracle
- number (NUMBER)
- text  (VARCHAR2)  
- clob  (CLOB)

###### SIZE INFO:
- text size <= 4000 sets "VARCHAR2" > 4000 sets "CLOB"

eg.
- type="number" size="14" => Mysql( bigint(14) ); Oracle( NUMBER(14) )
- type="text" size="300" => Mysql( text ); Oracle( VARCHAR2(300) )

If you want to create an "id-field" with auto_increment then you have to use the "special"
extension for the field definition. It will create an auto_increment field in Mysql and Oracle.
In Oracle will be set a sequence and a trigger to fill the id with continued numbers.

##### Xml Structure example:
    <migration>
      <version>1</version>
      <up>
      <createTable name="TABLENAME">
        <fields>
            <field name="ATTRIBUTE_NAME" type="ATTRIBUTE_TYPE" size="ATTRIBUTE_SIZE" />
            ...
            <field name="id" type="number" size="11" special="auto_increment" />
            ...
      </fields>
      </createTable>
      </up>
    </migration>

##### NOTICE:
The ATTRIBUTE_VALUE need to be base64_encoded. 

The AUTO_INCREMENT_VALUE defines which is the last used id.(if you set 120 the next insert will
get the id 121).

##### Xml Data example:

    <dbdata>
      <table name="TABLENAME" aifield="AUTO_INCREMENT_ATTRIBUTE" aivalue="AUTO_INCREMENT_VALUE" >
        <entry>
          <fields>
            <field name="ATTRIBUTE_NAME" type="ATTRIBUTE_TYPE">
              <![CDATA[ATTRIBUTE_VALUE]]>
            </field>
          </fields>
        </entry>
      </table>
    </dbdata>        
    
