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

function [outputID, json] = getMostRelevantRun( object, varargin )
% Finds the simulation run with the best matching solar wind conditions.
%
% Returns a cell array of Numerical Output ID's of the simulation run 
% that best matches the specified solar wind parameters. 
% One of the outputID's can be directly passed to functions
% getting data from the FMI IMPEx server, e.g. to function
% getDataPointValue( outputID, ... ). In addition this function returns
% the full json-formatted string returned by the FMI IMPEx server. The json
% string contains the exact SW parameters of the run to which outputID
% points to.
%
% Required argument:
% object -- One of 'Mercury', 'Venus', 'Earth', 'Mars'.
%
% Optional argument: 
% runCount -- the number of best matching runs to return. The default is 1.
% 
% Name,Value pairs (at least one of these is required):
% 'SW_Density', 'SW_Utot', 'SW_Temperature', 'SW_Btot', 'SW_Bx', 'SW_By',
%       'SW_Bz', 'solarF107'
%       -- each of these, if present, must be followed by the desired value
%       of the quantity (a scalar number in SI units), or a vector whose first
%       component is the desired value of the quantity, the second
%       component is the weight given to this parameter in calculating how
%       closely available simulations match, and the third component (can
%       be omitted) is the nominal scale of the parameter.
% 'SW_Function' -- a string describing a function, composed of the SW
%       parameters listed above and basic mathematical operations. If
%       present, the following additional Name,Value pairs can be given:
%       'FunctionValue' -- desired value of SW_Function (default: 0).
%       'FunctionWeight', 'FunctionScale' -- (defaults: 1).
%
% EXAMPLES
% outputID = getMostRelevantRun( 'Venus', 'SW_Utot', 6e5, 'SW_Bz', [5e-9 0.5 8e-9] );
% [outputID, json] = getMostRelevantRun( 'Earth', 3, 'SW_Function', 'SW_Bx/SW_Btot', 'FunctionValue', 0.9 );

% Written by Tiera Laitinen, Finnish Meteorological Institute, 2014.
% tiera.laitinen@fmi.fi 
% Part of the IMPEx-FMI-Matlab package.
% Free software without any warranty.
% Uses the Matlab-Fast-SOAP package by Edo Frederix.

%==========================================================================

% Sort and check arguments
if nargin < 3
    error('Supply planet name and at least one parameterName,parameterValue pair.');
end
args = inputParser;
addRequired( args, 'object', @ischar );
addOptional( args, 'runCount', 1, @isnumeric );
addParameter( args, 'SW_Density', nan, @isnumeric );
addParameter( args, 'SW_Utot', nan, @isnumeric );
addParameter( args, 'SW_Temperature', nan, @isnumeric );
addParameter( args, 'SW_Btot', nan, @isnumeric );
addParameter( args, 'SW_Bx', nan, @isnumeric );
addParameter( args, 'SW_By', nan, @isnumeric );
addParameter( args, 'SW_Bz', nan, @isnumeric );
addParameter( args, 'solarF107', nan, @isnumeric );
addParameter( args, 'SW_Function', 'omit', @ischar );
addParameter( args, 'FunctionValue', 0, @isnumeric );
addParameter( args, 'FunctionScale', 1, @isnumeric );
addParameter( args, 'FunctionWeight', 1, @isnumeric );

parse( args, object, varargin{:} );

% Collect parameters in a struct
sw.val.object.name = 'Object';
sw.val.object.val = args.Results.object;
sw.val.rc.name = 'RunCount';
sw.val.rc.val = num2str( args.Results.runCount );
sw.val.param.name = 'SW_parameters';

if ~ isnan( args.Results.SW_Density(1) )
    sw.val.param.val.density.name = 'SW_density';
    sw.val.param.val.density.val = buildParameter( args.Results.SW_Density );
end
if ~ isnan( args.Results.SW_Utot(1) )
    sw.val.param.val.utot.name = 'SW_Utot';
    sw.val.param.val.utot.val = buildParameter( args.Results.SW_Utot );
end
if ~ isnan( args.Results.SW_Temperature(1) )
    sw.val.param.val.temp.name = 'SW_Temperature';
    sw.val.param.val.temp.val = buildParameter( args.Results.SW_Temperature );
end
if ~ isnan( args.Results.SW_Btot(1) )
    sw.val.param.val.Btot.name = 'SW_Btot';
    sw.val.param.val.Btot.val = buildParameter( args.Results.SW_Btot );
end
if ~ isnan( args.Results.SW_Bx(1) )
    sw.val.param.val.Bx.name = 'SW_Bx';
    sw.val.param.val.Bx.val = buildParameter( args.Results.SW_Bx );
end
if ~ isnan( args.Results.SW_By(1) )
    sw.val.param.val.By.name = 'SW_By';
    sw.val.param.val.By.val = buildParameter( args.Results.SW_By );
end
if ~ isnan( args.Results.SW_Bz(1) )
    sw.val.param.val.Bz.name = 'SW_Bz';
    sw.val.param.val.Bz.val = buildParameter( args.Results.SW_Bz );
end
if ~ isnan( args.Results.solarF107(1) )
    sw.val.param.val.solar.name = 'Solar_F10.7';
    sw.val.param.val.solar.val = buildParameter( args.Results.solarF107 );
end
if ~ strcmp( args.Results.SW_Function, 'omit' )
    sw.val.param.val.function.name = 'SW_Function';
    sw.val.param.val.function.val.function.name = 'function';
    sw.val.param.val.function.val.function.val = args.Results.SW_Function;
    sw.val.param.val.function.val.value.name = 'value';
    sw.val.param.val.function.val.value.val = args.Results.FunctionValue;
    sw.val.param.val.function.val.scale.name = 'scale';
    sw.val.param.val.function.val.scale.val = args.Results.FunctionScale;
    sw.val.param.val.function.val.wei.name = 'weight';
    sw.val.param.val.function.val.wei.val = args.Results.FunctionWeight;
end

% Contact the SOAP server
soapMessage = createSoapMessage( ...
    'http://impex-fp7.fmi.fi', ...
    'getMostRelevantRun', ...
    sw, 'document');
response = callSoapService( ...
    'http://impex-fp7.fmi.fi/ws/Methods_FMI.php', ...
    'getMostRelevantRun', ...
    soapMessage);

% Extract resourceID from the answer
i1 = strfind( response, '<ns1:json_string>' ) + 17;
i2 = strfind( response, '</ns1:json_string>' ) - 1;
json = response(i1(1) : i2(1));

outputID = regexpi( json, 'spase://IMPEX/NumericalOutput/[^"]+', 'match' );

return

%==========================================================================

function parameter = buildParameter( vec )

parameter.value.name = 'value';
parameter.value.val = vec(1);
if length(vec) > 1
    parameter.weight.name = 'weight';
    parameter.weight.val = vec(2);
    if length(vec) > 2
        parameter.scale.name = 'scale';
        parameter.scale.val = vec(3);
    end
end
return
