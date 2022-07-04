# challenge-api

On this brief readme i will go trough my api architechture a littele bit.
This is a symfony REST API.

# controllers

Most of my controllers use the same pattern, each controller has a service related to it, on a controller is called, this one call his service, it's the services that do 
all the work and hand the result to the controller, which will in his turn return a http response to the client. The services may thow exceptions in case something went wrong,
those exceptions are catched by the controller who return a http error response to the client depending on the nature of the exception.

# authentication

Authentication is done using json web tokens, however i have 2 authentication guard in my app, one for token provided using request headers, and another one for token provided
using POST Form data. Why would i send a token using POST body ?? Well i encountered a pretty stressful problem while setting up the file download system on my client side.
The download route returns a streamed response from the backend, and when we try to fetch it using httpClient (I am using angular on my frontend), when we receives the data
and try to create a blob file from that data, the problem is the whole thing is saved in memory, so the client start lagging due to the lack of memory or even worse the whole
browser freeze because the RAM is full, so using httpClient was not a suitable way for me, but after some research i came up with another solution that consists of 
sumitting a hidden form with the file to be downloaded as target, and it works perfectly. However i am still looking for a better way to do it.

# file upload

I got inspired for the file upload system by the aws s3 MultipartUpload System. There are 3 controller routes in charge of upload, and they are structured as below:
- The first one is the upload initialization route, the client send a request to that route with the metadata of the file he is willing to upload, We will persist that metadata
in our database and generate a file key that will be attached to that future file, we return the newly created fileKey to our client;
- The second one is the route that receives the actual file data, it accepts a chunk data and the file key of the file that owns that chunk, once it received thoses two 
informations, he append the chunkdata to corresponding file by using file key;
- The third route is called by the client to notify the backend that he sent all the chunks, that way the backend can now start doing operations on that freshly uploaded file 
like doing some post uploads verifications, generating a file preview, encrypting it and more (For now the only post upload operation the backend is performing is encryption).

# file download

The file download is performed by a single controller, file is read from disk by chunks of 8mb, then each 8mb chunk is sent to the client, the controller response is a streamed
response.
