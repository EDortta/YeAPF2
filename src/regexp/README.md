 # REGEXP constants

 These expressions are used to format the data in a
 SanitizedKeyData instance or descendant
 There are two ways to format the data:
   1. input: when the data is passed to a SanitizedKeyData instance
   2. output: when the data is returned from a SanitizedKeyData instance
 
 As YeAPF was written while living in Brazil, the Brazilian
 regular expressions are used and tested. All the other ones need to be checked.
 Of course, you can write your own sed expressions.

 ## Example
 `YeAPF_SED_BR_IN_CNPJ` is the regular expression to accept CNPJ input in a SanitizedKeyData. The idea is to accept CNPJ with or without mask mantaining only the information. That is, the numbers in this case.

 This is defined as `/[^0-9]//`

 `YeAPF_SED_BR_OUT_CNPJ` is the formatter for CNPJ output on a SanitizedKeyData instance.

 This is defined as `/(\d{2})(\d{3})(\d{3})(\d{4})(\d{2})/$1.$2.$3\/$4-$5/` pay attention that, as we need the `/` symbol between params 3 and 4, we need to escape it as `/` is the symbol in regular expression substitution to indicate the search, the replacement, and the parameters.