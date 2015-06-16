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

function data = votread( filename )
% Reads the variables from a simple VOTable file into a Matlab struct.
%
% Note that this function is rather slow when reading large files.
% The function cannot handle files with more than one table.
%
% Written by Tiera Laitinen, Finnish Meteorological Institute, 2014.
% tiera.laitinen@fmi.fi 
% Part of the IMPEx-FMI-Matlab package.
% Free software without any warranty.

%========================================================================

timeFormat = 'yyyy-mm-ddTHH:MM:SS.FFF';  

maxVariables = 100;  
% maximum number of variable names allowed in the file. This exists to
% prevent the code from entering an infinite loop if something goes wrong
% when reading variable names.

str = fileread( filename );

% Find the (first) table element from the file. Only one table will be read
% by this function.
[iTable, att] = findElement( str, 'TABLE' );
str = str( iTable(1) : iTable(2) );     % Shorten the string to only the table.

% Find out the name of rows in the table from the attributes.
nrows = 1;
for i = 1 : length(att)
    if strcmp( att{i}{1}, 'nrows' )
        nrows = str2double( att{i}{2} );
    end
end

% Find description. 
iDesc = findElement( str, 'DESCRIPTION' );

%-------------------------------------------------------------------------
% Find the names and dimensions of the variables.
nfields = 0;
varname = cell(1);
[ii, att] = findElement( str, 'FIELD' );
while ii(1) > 0
    nfields = nfields + 1;
    varname{nfields} = strcat('var', num2str(nfields) );
    varsize(nfields) = 1;
    for j = 1:length(att)
        switch att{j}{1}
            case 'name'
                varname{nfields} = att{j}{2};
            case 'arraysize'
                varsize(nfields) = str2double( att{j}{2} );
            case 'ucd'
                if strcmp( att{j}{2}, 'time.epoch' )
                    varsize(nfields) = -1;  % This denotes the time field.
                end
        end
    end
    alku = ii(1);
    [ii, att] = findElement( str(ii(1) : length(str)), 'FIELD' );
    if ii(1) > 0
        ii(1) = ii(1) + alku - 1;
    end
    
    if nfields > maxVariables
        error('Error reading variable names.');
    end
end

% Find a parameter: EnergyRange (if exists)
[~, att] = findElement( str, 'PARAM' );
energyRange = 0;
if length(att) > 0
    if strcmpi( att{1}{2}, 'EnergyRange' )
        for j = 1:length(att)
            switch att{j}{1}
                case 'value'
                    energyRange = str2num( att{j}{2} );
            end
        end
    end
end
    

%-------------------------------------------------------------------------
% Start building the return struct. 

% First include description. (This will be included always; if description 
% was not present in the file, it will be and empty string here.)
data = struct( 'Description', str(iDesc(1) : iDesc(2)) );

% Add the energy range, if exists:
if length(energyRange) > 1
    data.EnergyRange = energyRange;
end

% Read the data into the struct.
iCont = findElement( str, 'TABLEDATA' );
str = str( iCont(1) : iCont(2) );   % shorten the string 

loppu = length(str);
irow = [1 1];
for i = 1:nrows
    alku = irow(2);
    irow = findElement( str(irow(2) : loppu), 'TR' ) + alku - 1;
    ifield = [irow(1) irow(1)];  
    for j = 1:nfields
        alku = ifield(2);
        ifield = findElement( str( ifield(2) : irow(2)), 'TD' ) + alku - 1;
        if varsize(j) == 1
            data.(varname{j})(i) = str2double( str(ifield(1) : ifield(2)) );
        elseif varsize(j) == -1
            data.(varname{j})(i) = datenum( str(ifield(1) : ifield(2)-1), timeFormat );
        elseif varsize(j) > 1
            data.(varname{j})(i,:) = str2num( str(ifield(1) : ifield(2)) );
        else
            error('Unknown variable size/type');
        end
    end
end

return


%=========================================================================

function [iContent, attributes] = findElement( str, name )
% iContent = the first and last index of the content of the element.
% Element not found -> iContent = [0 0].
% Element has no content -> iContent = [i 0], where i is the index of the
% first character after the tag.

iEnd = length( str );

% find the opening tag:
iTag1 = regexp( str, strcat('<', name, '[\s>]'), 'once' );

if isempty( iTag1 )     % element was not found
    iContent = [0 0];
    attributes = cell(0);
    return
end

% Extract attributes:
if str( iTag1 + length(name) + 1 ) == '>'       % an element without attributes
    iContent(1) = iTag1 + length(name) + 2;
    attributes = cell(0);    
else                                            % an element with attributes
    iTag2 = iTag1 - 1 + regexp( str(iTag1 : iEnd), '>', 'once' );
    iContent(1) = iTag2 + 1;
    attributes = regexp( str(iTag1:iTag2), '\s(?<name>\w+)="(?<value>[^"]*)"', 'tokens' );
    if str(iTag2 - 1) == '/'
        iContent(2) = 0;
        return
    end
end

% Find the closing tag:
iContent(2) = iContent(1) - 2 ...
    + regexp( str(iContent(1) : iEnd), strcat('</', name, '>'), 'once' );

return



