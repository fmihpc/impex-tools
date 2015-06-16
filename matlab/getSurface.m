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

function filestr = getSurface( resourceID, planeNormal, planePoint, fileName, varargin )
% GETSURFACE  Get data from FMI IMPEx server on a plane grid.
%
% Required parameters:
% resourceID -- Impex ID of the desired simulation run. Use
%       getMostRelevantRun to obtain this.
% planeNormal -- a vector perpendicular to the desired plane
% planePoint -- coordinates of a point in the desired plane
% fileName -- The data is saved in this file in your working directory.
%
% Optional Name,Value pairs:
% 'variables' -- list of variable names to be printed in the output file
%       (space separated char array), default: all.
% 'resolution' -- distance between data points on the plane. The default is
%       the grid cell size used in the simulation.
% 'interpolationMethod' -- either 'linear' or 'nearestGridPoint'.
% 'outputFileType' -- either 'VOTable' or 'netCDF'.
%
% See also: GETMOSTRELEVANTRUN, GETDATAPOINTVALUE

% Written by Tiera Laitinen, Finnish Meteorological Institute, 2014.

%=========================================================================

% Parse and check input arguments
args = inputParser;
addRequired( args, 'resourceID', @ischar );
addRequired( args, 'planeNormal', @isnumeric );
addRequired( args, 'planePoint', @isnumeric );
addRequired( args, 'fileName', @ischar );
addParameter( args, 'variables', '', @ischar );
addParameter( args, 'resolution', -1, @isnumeric );
addParameter( args, 'interpolationMethod', '', @ischar );
addParameter( args, 'outputFileType', '', @ischar );
parse( args, resourceID, planeNormal, planePoint, fileName, varargin{:} );

k = size(planeNormal);
if k(1) > 1
    planeNormal = planeNormal';     % make them row vectors
end
k = size(planePoint);
if k(1) > 1
    planePoint = planePoint';
end
if length(planeNormal) ~= 3 || length(planePoint) ~= 3
    error('planeNormal and planePoint must be vectors of length 3.');
end
pituus = sqrt( planeNormal(1)^2 + planeNormal(2)^2 + planeNormal(3)^2 );
planeNormal = planeNormal / pituus;

% Compose parameter data structure
data.val.resourceID.name = 'ResourceID';
data.val.resourceID.val = resourceID;
data.val.resourceID.type = '{http://impex-fp7.oeaw.ac.at}ResourceID';
data.val.normal.name = 'PlaneNormalVector';
data.val.normal.val = num2str( planeNormal );
data.val.point.name = 'PlanePoint';
data.val.point.val = num2str( planePoint );
if ~ strcmp( args.Results.variables, '' )
    data.val.variable.name = 'Variable';
    data.val.variable.val = args.Results.variables;
    data.val.variable.type = '{http://impex-fp7.fmi.fi}Variable';
end
if args.Results.resolution > 0
    data.val.extra.name = 'extraParams';
    data.val.extra.val.resolution.name = 'Resolution';
    data.val.extra.val.resolution.val = args.Results.resolution;
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
    'getSurface', ...
    data, 'document');
response = callSoapService( ...
    'http://impex-fp7.fmi.fi/ws/Methods_FMI.php', ...
    'getSurface', ...
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
