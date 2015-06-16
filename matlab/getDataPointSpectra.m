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

function filestr = getDataPointSpectra( resourceID, urlPoints, fileName, varargin )
% GETDATAPOINTSPECTRA  Get energy spectra from FMI Impex server at specified points.
%
% Required parameters:
% resourceID -- Impex ID of the desired simulation run. Use
%       getMostRelevantRun to obtain this.
% urlPoints -- URL of a VOTable file containing the coordinates of the points
%       where the data values are to be calculated. Use
%       makeCoordinateVotable to obtain this.
% fileName -- The data is saved in this file in your working directory.
%
% Optional Name,Value pairs:
% 'interpolationMethod' -- either 'linear' or 'nearestGridPoint'.
% 'outputFileType' -- currently only 'VOTable' supported
% 'energyChannel' -- List of energy channel band names as defined in 
%       Spase/NumericalOutput/Parameter/Particle/EnergyRang/Bin/BandName
%
% See also: GETDATAPOINTSPECTRASPACECRAFT, GETDATAPOINTVALUE, GETMOSTRELEVANTRUN

% Written by Tiera Laitinen, Finnish Meteorological Institute, 2014.
% Part of the IMPEx-FMI-Matlab package.
% Free software without any warranty.

%========================================================================

% Parse and check input arguments
args = inputParser;
addRequired( args, 'resourceID', @ischar );
addRequired( args, 'urlPoints', @ischar );
addRequired( args, 'fileName', @ischar );
addParameter( args, 'interpolationMethod', '', @ischar );
addParameter( args, 'outputFileType', '', @ischar );
addParameter( args, 'energyChannel', '', @ischar );
parse( args, resourceID, urlPoints, fileName, varargin{:} );

% Compose parameter data structure
data.val.resourceID.name = 'ResourceID';
data.val.resourceID.val = resourceID;
data.val.resourceID.type = '{http://impex-fp7.oeaw.ac.at}ResourceID';
data.val.url_xyz.name = 'url_XYZ';
data.val.url_xyz.val = urlPoints;
data.val.url_xyz.type = '{http://www.w3.org/2001/XMLSchema}anyURI';
if ~ strcmp( args.Results.energyChannel, '' )
    data.val.variable.name = 'EnergyChannel';
    data.val.variable.val = args.Results.energyChannel;
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
    'getDataPointSpectra', ...
    data, 'document');
response = callSoapService( ...
    'http://impex-fp7.fmi.fi/ws/Methods_FMI.php', ...
    'getDataPointSpectra', ...
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
