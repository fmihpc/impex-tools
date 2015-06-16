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

function filestr = getParticleTrajectory( resourceID, urlParticles, fileName, varargin )
% GETPARTICLETRAJECTORY  Get particle trajectories from FMI Impex server.
%
% Required parameters:
% resourceID -- Impex ID of the desired simulation run. Use
%       getMostRelevantRun to obtain this.
% urlParticles -- URL of a VOTable file containing the start points and 
%       properties of the particles. Use makeParticleVotable to obtain this.
% fileName -- The data is saved in this file in your working directory.
%
% Optional Name,Value pairs:
% 'direction' -- direction of field line tracing. One of 'Forward'
%       (default), 'Backward', 'Both'
% 'stepSize' -- Length of one time step in particle tracing.
% 'maxSteps' -- maximum number of time steps per trace, default = 200.
% 'stopRadius' -- The field/stream line tracing is stopped if the 
%       distance from the center of object is LESS than this. Default = 0.
% 'stopRegion' -- [Xmin Xmax Ymin Ymax Zmin Zmax], limits of a box
%       OUTSIDE of which field/stream line tracing is stopped. 
%       Default = entire simulation box. 
% 'outputFileType' -- either 'VOTable' or 'netCDF'.
%
% See also MAKEPARTICLEVOTABLE, GETMOSTRELEVANTRUN, GETFIELDLINE

% Written by Tiera Laitinen, Finnish Meteorological Institute, 2014.
% Part of the IMPEx-FMI-Matlab package.
% Free software without any warranty.

%========================================================================

% Parse and check input arguments
args = inputParser;
addRequired( args, 'resourceID', @ischar );
addRequired( args, 'urlParticles', @ischar );
addRequired( args, 'fileName', @ischar );
addParameter( args, 'direction', '', @ischar );
addParameter( args, 'stepSize', -1, @isnumeric );
addParameter( args, 'maxSteps', -1, @isnumeric );
addParameter( args, 'stopRadius', -1, @isnumeric );
addParameter( args, 'stopRegion', nan, @isnumeric );
addParameter( args, 'outputFileType', '', @ischar );
parse( args, resourceID, urlParticles, fileName, varargin{:} );

if ~ isnan( args.Results.stopRegion )
    k = size( args.Results.stopRegion );
    if k(1) > 1
        args.Results.stopRegion = args.Results.stopRegion';
    end
end

% Compose parameter data structure
data.val.resourceID.name = 'ResourceID';
data.val.resourceID.val = resourceID;
data.val.resourceID.type = '{http://impex-fp7.oeaw.ac.at}ResourceID';
data.val.url_xyz.name = 'url_XYZ';
data.val.url_xyz.val = urlParticles;
data.val.url_xyz.type = '{http://www.w3.org/2001/XMLSchema}anyURI';
if ~ strcmp( args.Results.direction, '' )
    data.val.extra.name = 'extraParams';
    data.val.extra.val.dir.name = 'Direction';
    data.val.extra.val.dir.val = args.Results.direction;
end
if args.Results.stepSize > 0
    data.val.extra.name = 'extraParams';
    data.val.extra.val.step.name = 'StepSize';
    data.val.extra.val.step.val = args.Results.stepSize;
end
if args.Results.maxSteps > 0
    data.val.extra.name = 'extraParams';
    data.val.extra.val.max.name = 'MaxSteps';
    data.val.extra.val.max.val = args.Results.maxSteps;
end
if args.Results.stopRadius > 0
    data.val.extra.name = 'extraParams';
    data.val.extra.val.radius.name = 'StopCondition_Radius';
    data.val.extra.val.radius.val = args.Results.stopRadius;
end
if ~ isnan( args.Results.stopRegion )
    data.val.extra.name = 'extraParams';
    data.val.extra.val.region.name = 'StopCondition_Region';
    data.val.extra.val.region.val = num2str( args.Results.stopRegion );
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
    'getParticleTrajectory', ...
    data, 'document');
response = callSoapService( ...
    'http://impex-fp7.fmi.fi/ws/Methods_FMI.php', ...
    'getParticleTrajectory', ...
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
