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

% IMPEx-FMI-Matlab usage example 5: Particle spectra

% This sample script shows how to plot particle spectra from the FMI HWA
% hybrid simulations.

% Written by Tiera Laitinen, Finnish Meteorological Institute, 2014.


%-------------------------------------------------------------------------
% 1. Find the resource ID of suitable simulation results.
%-------------------------------------------------------------------------

% Particle spectra are available only for selected hybrid simulation runs.
% We suggest finding the suitable output ID e.g. from the IMPEx tree.xml
% file or by browsing the IMPEx portal at http://impex-portal.oeaw.ac.at
% Here is a sample resource with proton spectra at Venus:

idspec = 'impex://FMI/NumericalOutput/HYB/venus/run01_venus_nominal_spectra_20140417/H+_spectra';


%-------------------------------------------------------------------------
% 2. Choose points where to get data and write a VOTable file.
%-------------------------------------------------------------------------

% All quantities must be given in SI units, e.g. coordinate values in
% metres. The radius of Venus is:

Rvenus = 6052000;

% This time we choose only one point in the bow shock and one behind the
% planet:

coord = [1.2 0 0; -1.2 0 0] * Rvenus;

% Function makeCoordinateVotable creates a VOTable file containing the
% coordinates, stores the file temporarily at an FMI server and returns the
% URL to the file:

url = makeCoordinateVotable( coord );


%-------------------------------------------------------------------------
% 3. Get H+ spectrum at the specified point.
%-------------------------------------------------------------------------

% Get spectrum using the getDataPointSpectra function. This function, as
% all get... -functions, returns the name of the save file, so one can use
% a variable to pass it to a file reading function instead of typing the 
% name explicitly again.

file = getDataPointSpectra( idspec, url, 'spectrum.vot' );
spectrum = votread( file );

% Plot the data:
n = length( spectrum.ParticleFlux(1,:) );

figure(1);
clf;
loglog( spectrum.EnergyRange(1:n), spectrum.ParticleFlux(1,:), 'r' );
hold on;
loglog( spectrum.EnergyRange(1:n), spectrum.ParticleFlux(2,:), 'b' );
legend('In front', 'Behind');
xlabel('Energy [eV]');
ylabel('Particle flux [m^{-2} s^{-1} sr^{-1} eV^{-1}]');


%-------------------------------------------------------------------------
% 4. Get H+ spectrum at Venus Express orbit.
%-------------------------------------------------------------------------

% Spectra as a function of time on a spacecraft orbit is obtained using
% getDataPointSpectraSpacecraft. Required arguments are resource ID,
% spacecraft name, start and stop time, time resolution (in seconds or as
% an ISO 8601 duration string) and save file name.

t_start = datenum(2010,8,2,7,0,0);
t_stop = datenum(2010,8,2,9,0,0);
file = getDataPointSpectraSpacecraft( idspec, 'VEX', ...
    t_start, t_stop, 120, 'scspectrum.vot' );
vexspectra = votread( file );

% Plot the data:

% Create a logaritmic energy axis:
nn = size( vexspectra.ParticleFlux );
logEnergy = log10( vexspectra.EnergyRange(1 : nn(2)) );

% Create a time vector in minutes (interval was 120 s = 2 min):
t = linspace( 0, 2*nn(1), nn(1) );

figure(2);
clf;
pcolor( t, logEnergy, log10(vexspectra.ParticleFlux)' );
colorbar;
xlabel('Time (minutes from start)');
ylabel('Log 10 of energy [eV]');
title('Log 10 of particle flux [m^{-2} s^{-1} sr^{-1} eV^{-1}]');
shading flat;
