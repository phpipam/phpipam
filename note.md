##enable protocol handler


##mstsc
download RdpProtocolHandler.7z in your pc from the given link. Place the .exe file in the desired location on your PC, open command prompt as administrator. got to the exe file location using the CD command. 
then run with /install parameter like this:
 "dpProtocoleHandler.exe /install" 

Link to download RDP protocol handler https://github.com/konradsikorski/RdpProtocolHandler

##Telnet
for telnet download and install putty from the given link. 
https://www.chiark.greenend.org.uk/~sgtatham/putty/latest.html .
Make sure you installed it. then do the following registry change.

HKEY_CLASSES_ROOT\telnet\shell\open\command to be: "\path\to\putty.exe" %1

example "C:\Program Files\PuTTY\putty.exe" %1

##ssh
Normally you don't need to do anything for ssh it will automatically happen as you installed putty. other wish you may change it manually like the telnet 
