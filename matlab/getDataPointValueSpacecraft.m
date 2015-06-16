% This file is part of the FMI IMPEx tools.
%
% Copyright 2014- Finnish Meteorological Institute
%
% This program is free software: you can redistribute it and/or modify
% it under the terms of the GNU General Public License as published by
% the Free Software Foundation, either version 3 of the License, or
% (at your option) any later version.
%
% This program is distributed in the hope that it will be useful,
% but WITHOUT ANY WARRANTY; without even the implied warranty of
% MERCHANTABILITY or FITNESS FOR A PARTICULAR PURPOSE.  See the
% GNU General Public License for more details.
%
% You should have received a copy of the GNU General Public License
% along with this program.  If not, see <http://www.gnu.org/licenses/>.

function filestr = getDataPointValueSpacecraft( resourceID, sc, start, stop, sampling, fileName, varargin )
% GETDATAPOINTVALUESPACECRAFT  Get simulation data from FMI IMPEx server on a spacecraft track.
%
% Required parameters:
% resourceID -- Impex ID of the desired simulation run. Use
%       getMostRelevantRun to obtain this.
% sc -- name of the spacecraft as defined by AMDA
% start -- data start time as either Matlab DateNumber or ISO 8601 string 
% stop -- data end time as either Matlab DateNumber or ISO 8601 string 
% sampling -- time interval between data points either in seconds (integer)
%       or as an ISO 8601 duration string
% fileName -- The data is saved in this file in your working directory.
%
% Optional Name,Value pairs:
% 'variables' -- list of variable names to be printed in the input file
%       (space separated char array)
% 'interpolationMethod' -- either 'linear' or 'nearestGridPoint'.
% 'outputFileType' -- either 'VOTable' or 'netCDF'.
%
% See also: GETDATAPOINTVALUE, GETMOSTRELEVANTRUN, DATENUM

% Written by Tiera Laitinen, Finnish Meteorological Institute, 2014.

timeFormat = 'yyyy-mm-ddTHH:MM:SS.FFF';

%=========================================================================

% Parse and check input arguments
args = inputParser;
addRequired( args, 'resourceID', @ischar );
addRequired( args, 'sc', @ischar);
addRequired( args, 'start');
addRequired( args, 'stop');
addRequired( args, 'sampling');
addRequired( args, 'fileName', @ischar );
addParameter( args, 'variables', '', @ischar );
addParameter( args, 'interpolationMethod', '', @ischar );
addParameter( args, 'outputFileType', '', @ischar );
parse( args, resourceID, sc, start, stop, sampling, fileName, varargin{:} );

if isnumeric( start )
    start = datestr( start, timeFormat );
end
if isnumeric( stop )
    stop = datestr( stop, timeFormat );
end

if isnumeric( sampling )
    days = floor( sampling / 86400 );
    hours = floor( (sampling - 86400*days) / 3600 );
    mins = floor( (sampling - 86400*days - 3600*hours) / 60 );
    secs = rem( sampling, 60 );
    
    sampling = 'P';
    if days > 0
        sampling = strcat( sampling, sprintf( '%iD', days ));
    end
    sampling = strcat( sampling, 'T' );
    if hours > 0
        sampling = strcat( sampling, sprintf( '%iH', hours ));
    end
    if mins > 0
        sampling = strcat( sampling, sprintf( '%iM', mins ));
    end
    if secs > 0
        sampling = strcat( sampling, sprintf( '%iS', secs ));
    end
end

% Compose parameter data structure
data.val.resourceID.name = 'ResourceID';
data.val.resourceID.val = resourceID;
data.val.resourceID.type = '{http://impex-fp7.oeaw.ac.at}ResourceID';
data.val.sc.name = 'Spacecraft_name';
data.val.sc.val = sc;
data.val.start.name = 'StartTime';
data.val.start.val = start;
data.val.stop.name = 'StopTime';
data.val.stop.val = stop;
data.val.sampling.name = 'Sampling';
data.val.sampling.val = sampling;
if ~ strcmp( args.Results.variables, '' )
    data.val.variable.name = 'Variable';
    data.val.variable.val = args.Results.variables;
    data.val.variable.type = '{http://impex-fp7.fmi.fi}Variable';
end
if ~ strcmp( args.Results.interpolationMethod, '' )
    data.val.extra.name = 'extraParams';
    data.val.extra.val.interpolationMethod.name = 'InterpolationMethod';
    data.val.extra.val.interpolationMethod.val = args.Results.interpolationMethod;
    data.val.extra.val.interpolationMethod.type = '{http://impex-fp7.fmi.fi}enumInterpolation';
end
if ~ strcmp( args.Results.outputFileType, '' )
    data.val.extra.name = 'extraParams';
    data.val.extra.val.outputFileType.name = 'OutputFileType';
    data.val.extra.val.outputFileType.val = args.Results.outputFileType;
    data.val.extra.val.outputFileType.type = '{http://impex-fp7.fmi.fi}OutputFormat';
end

% Call the SOAP service
soapMessage = createSoapMessage( ...
    'http://impex-fp7.fmi.fi', ...
    'getDataPointValueSpacecraft', ...
    data, 'document');
response = callSoapService( ...
    'http://impex-fp7.fmi.fi/ws/Methods_FMI.php', ...
    'getDataPointValueSpacecraft', ...
    soapMessage);

% Parse the SOAP response, get data file and save
i1 = strfind( response, '<ns1:anyURI>' ) + 12;
i2 = strfind( response, '</ns1:anyURI>' ) - 1;
urlAns = response(i1(1) : i2(1));

if strncmp( urlAns, 'http', 4 )
    [filestr, fileStatus] = urlwrite( urlAns, fileName );
else    
    display('SOAP error: ');
    display( urlAns );
    return;
end

if ~ fileStatus
    display( urlAns );
    error('Could not save the data file from the address above');
end

return;
