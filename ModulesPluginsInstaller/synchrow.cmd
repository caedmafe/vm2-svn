
Echo Start START Start START Start START Start START Start START Start START Start START Start START Start START Start START Start START Start START Start START Start START Start START Start START Start START Start START Start START Start START Start START Start START Start START Start START Start START

SET msource=%USERPROFILE%\Coden\workspace\VM2_j17
SET aiodest=%USERPROFILE%\Coden\workspace\VM2-AllInOnceInstaller
SET mdest=%USERPROFILE%\Coden\workspace\VM2-AllInOnceInstaller\admin
set OLDDIR=%CD%

xcopy "%aiodest%\script.vmallinone.php" "%mdest%\script.vmallinone.php" /Y

ECHO Plugins 
xcopy %msource%\plugins\vmshipment %mdest%\plugins\vmshipment /Y /E

xcopy %msource%\plugins\vmpayment %mdest%\plugins\vmpayment /Y /E

xcopy %msource%\plugins\vmcustom %mdest%\plugins\vmcustom /Y /E

xcopy %msource%\plugins\search\virtuemart %mdest%\plugins\search\virtuemart /Y /E

ECHO Modules
xcopy %msource%\modules %mdest%\modules /Y /E

ECHO Languages
xcopy %msource%\language %mdest%\languageFE /Y /E

xcopy %msource%\administrator\language %mdest%\languageBE /Y /E

xcopy C:\Users\Milbo\Coden\workspace\VM2_j17\language C:\Users\Milbo\Coden\workspace\VM2-AllInOnceInstaller\admin\languageFE /Y /E