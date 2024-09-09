# How regular expressions are used

There are three contstraints that uses regular expressions: `regExpression`, `sedInputExpression` and `sedOutputExpression`.

### regExpression
`regExpression` is a regular expression that is used to match the input string. If the input string does not match the regular expression, an exception is thrown.

### sedInputExpression
`sedInputExpression` is a regular expression that is used sanitize the input string. So, this expression has a `serach` part and a `replace` part. A common example is to remove all non number characters. In such case, `sedInputExpression` is `/[^0-9]//`.

### sedOutputExpression
`sedOutputExpression` is a regular expression that is used to format the output string. As in `sedInputExpression`, it has a `search` part and a `replace` part. A valid example of `sedOutputExpression` is `/(\d{5})(\d{4})?/$1-$2/`.

## Usage
You is not required to use all three regular expressions. You can use only one or two. All will depend on your use case. Anyway the `regExpression` is vital to accept the input string for example in an API call, so usually, you will want to use it there.

`sedInputExpression` will be used before accept the value and store it in a database, for example. That's the reason why you have a `sedOutputExpression` to format the output string that comes from the database. So, in such vision, `sedInputExpression` is usually used to keep data clean and small keeping it easy to find parts of formatted strings instead of having to parse them or to obligate the user to do it.

In the same way, `sedOutputExpression` will _always_ format the output string in a way the final user can easly read it and understand it.

## Priority of the regular expressions
The internal priority of the regular expressions when you is trying to save a value is: `sedInputExpression` > `regExpression` > `length` of the data is tested > accept the data.

When you request the value again, it is returned as a result of `sedOutputExpression` if it is defined.