##enable protocol handeler


##mstsc
exract RdpProtocolHandler.7z in your pc . Place the .exe file in desired location on your PC, open comman prompt as administrator . forowerd 
to the exe file location . 
then run with /install parameter like this:
 "dpProtocoleHandler.exe /install" 

for more info https://github.com/konradsikorski/RdpProtocolHandler

##Telnet
for telnet download and install putty from the given link. 
https://www.chiark.greenend.org.uk/~sgtatham/putty/latest.html .
Make sure you installed it . then do the following registry change .

HKEY_CLASSES_ROOT\telnet\shell\open\command to be: "\path\to\putty.exe" %1

example "C:\Program Files\PuTTY\putty.exe" %1

