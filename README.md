yii-jar
=======

Extension for the Yii PHP Framework that provides a standard Json Ajax Response.

The Jar extension for [Yii Framework](http://www.yiiframework.com/) sets a standard response for 
JSON based on the [JSend Standard](http://labs.omniti.com/labs/jsend).


Features
-------------------
* Standard structure for JSON responses.
* Simple model response creation.
* Model support for 1:1 relations via dot notation.
* Support for [Yii CDataProviders](http://www.yiiframework.com/doc/api/1.1/CDataProvider).
* Send function that correctly sets the Content-type

JSend Standard Summary
-------------------

<table>
<thead><tr>
<th>Type</th>
<th>Description</th>
<th>Required Keys</th>
<th>Optional Keys</th>
</tr></thead>
<tbody>
<tr><td>success</td><td>All went well, and (usually) some data was returned.</td><td>status, data</td><td></td></tr>
<tr><td>fail</td><td>There was a problem with the data submitted, or some pre-condition of the API call wasn't satisfied</td><td>status, data</td><td></td></tr>
<tr><td>error</td><td>An error occurred in processing the request, i.e. an exception was thrown</td><td>status, message</td><td>code, data</td></tr>
</tbody>
</table>

Installation
-------------------
Drop Jar.php into the application.extensions directory.

Example Responses
===================

Example Success Response:
```javascript
{
  status : "success",
  data : {
    "posts" : [
      { "id" : 1, "title" : "A blog post", "body" : "Some useful content" },
      { "id" : 2, "title" : "Another blog post", "body" : "More content" },
    ]
  }
}
```

Example Error Response:
```javascript
{
  "status" : "error",
  "message" : "A title is required"
}
```

Methods
===================
Note that almost all the methods support chaining.

Data
-------------------
```$attributes``` is an array that will strip down the given active record models to only those
parameters. It supports dot notation for 1:1 relations, so ```Model->relation->variable``` can be gotten via
relation.variable and is then placed into an object for that relation.

* ```addData([string Key], Value)``` - Add a simple key, value into the data array. Value can be anything that [CJSON::encode supports](http://www.yiiframework.com/doc/api/1.1/CJSON#encode-detail).
* ```unsetData([string Key])``` - Removes data from the data array
* ```addModel(Model, $attributes)``` - Adds a model to the array for the model's class. ```resp.data.Model[0].attribute```
* ```addModels(Models[], $attributes)``` - Adds an array of models to the array for the model's class. ```resp.data.Model[]```
* ```addDataProvider(CDataProvider, $attributes)``` - Handles CActiveRecordDataProvider in the same way as ```addModels()```, but other CDataProviders
by adding their rows to ```javascript resp.data.row[]```

Setting Status
-------------------
* Default success. (Returns: status, data)
* ```fail()``` - Things like validation errors. (Returns: status, data)
* ```error('An error message string.', [Error Code])``` - (Returns: status, message, code(optional), data(optional))
* ```reset()``` - Resets all the data to nothing and the status to success.

Testing Status
-------------------
* ```hasSuccess()``` - Boolean Return
* ```hasError()``` - Boolean Return
* ```hasFail()``` - Boolean Return

Getting Data Back Out
-------------------
* ```getJson()``` - Returns the ```CJSON::encode()``` string
* ```getData()``` - Returns the data array

Sending Data
-------------------
* ```send()``` - Echoes out the Json and ends the Application with ```Yii::app()->end();```
